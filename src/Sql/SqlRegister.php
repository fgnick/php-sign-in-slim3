<?php
namespace Gn\Sql;

use Exception;

use Gn\Lib\GnRandom;
use Gn\Lib\JwtPayload;
use Gn\Lib\StrProc;
use Gn\Lib\Uuid;
use Gn\Lib\Globals;
use Gn\Lib\ValueValidate;

use Slim\Container;

/**
 * It works for register. E.g. login, sign-up, logout, reset password, forget password, OTP, ....
 *
 * @author Nick Feng
 * @since 1.0
 */
class SqlRegister extends SqlBase
{
    /**
     * @var SqlServConn
     */
    private $conn;

    /**
     * @var array it is Slim global settings' array.
     */
    private $settings;

    /**
     * Constructor and get pdo connection.
     * 
     * @param Container $container Slim container
     */
    public function __construct( Container $container ) 
    {
        parent::__construct(); 
        // connect to database
        $this->settings = $container->get('settings');
        $this->conn = new SqlServConn( $this->settings['db']['main'] );
    }

    /**
     * Member invitation processing.
     * 
     * NOTE: At the beginning of a member invited, the member status must start from 3(initializing).
     * 
     * @param int    $company_id
     * @param string $email sign-up for unique key.
     * @param int    $type 
     * @param int    $role
     * @return array|int if it is success, it will return a payload array with mem_id; Otherwise, it will return an error code.
     */
    public function initMember( int $company_id, string $email, int $type, int $role )
    {
        // characters filter
        $email = filter_var( trim( $email ), FILTER_SANITIZE_EMAIL );
        // check format each field.
        // invitee can not be type 1 (super admin) on frontend, only backend can assign 1
        if ( $company_id <= 0 || $type <= 1 || $type > 3 || $role <= 0 ) {
            return static::PROC_INVALID;
        } else if ( !ValueValidate::is_email( $email ) ) {
            return static::PROC_INVALID;
        }
        
        // Step 1: look up the maximum number of invitation of company/group,
        //         and ensure the role is existed, and it belongs to the company(not a default of plan.)
        //         只有 owner 才可以使用 1 ~ 5 的 role id，因為他們的 company_id = 0
        $output = $this->conn->selectTransact(
            'SELECT ( p.member_num > ( SELECT COUNT(*) FROM member WHERE company_id =' . $company_id . ') ) AS is_able,
                    ( EXISTS( SELECT id FROM roles WHERE id = ' . $role . ' AND company_id =' . $company_id . ') ) AS is_role,
                    ( SELECT status FROM member WHERE email = ? AND company_id =' . $company_id . ') AS is_resume
             FROM company AS c
             INNER JOIN company_plan AS p ON p.id = c.plan_id 
             WHERE c.id =' . $company_id, [ $email ]
        );

        if ( is_int( $output ) ) {
            return $output;
        } else if ( count( $output ) !== 1 ) {
            return static::PROC_SERV_ERROR;
        }
        $row = $output[0];

        // Step 2: if it is not overflow, it accesses to create a new member in the company/group.
        if ( $row['is_able'] == 0 || $row['is_role'] == 0 ) { // it is out of plan member invitation maximum.
            return static::PROC_DATA_FULL;
        }

        // Step3: 先檢查是不是 已經存在這個 email，但是 status 被轉成 0 (removed)。
        //        有的話，就直接轉換 status 為 3 (初始化邀請狀態)即可。否則，就是新增一個新的帳號。
        if ( is_int($row['is_resume']) ) { // member account in removed status, you should resume it now!
            if ( $row['is_resume'] !== 0 ) {
                $output = static::PROC_DUPLICATE;
            } else {
                // NOTE: it may change the company, type, and role.
                $output = $this->conn->writeTransact(
                    'UPDATE member SET status = 3, company_id = '.$company_id.', type = '.$type.', role_id = '.$role.
                    ' WHERE email = ? AND company_id ='.$company_id,
                    [ $email ]
                );
                if ( $output === static::PROC_OK ) {
                    $output = $this->conn->selectTransact(
                        'SELECT id FROM member WHERE email = ? AND company_id ='.$company_id,
                        [ $email ]
                    );
                    if ( is_int( $output ) ) {
                        return $output;
                    } else if ( count( $output ) !== 1 ) {
                        return static::PROC_SERV_ERROR;
                    }
                    $output = [
                        'mem_id' => $output[0]['id']
                    ];
                } else {
                    $output = static::PROC_FAIL;
                }
            }
        } else {    // $row['is_resume'] = NULL the email is not existed in the company/group request given.
            $passwd = hash( 'sha512', JwtPayload::genSalt() );
            $bcrypt = password_hash( $passwd, PASSWORD_BCRYPT ); // NOTE: don't forget to use password_hash( password, PASSWORD_BCRYPT )

            // IMPORTANT: system can not use the same email address to register again.
            $output = $this->conn->writeTransact(
                'INSERT INTO member ( company_id, email, pw, status, type, role_id )
                 SELECT ?, ?, ?, 3, ?, ? FROM DUAL WHERE
                 NOT EXISTS ( SELECT id FROM member WHERE email = ? )',
                [ $company_id, $email, $bcrypt, $type, $role, $email ]
            );
            if ( $output === static::PROC_OK ) {
                // lastInsertId() 不是很可靠。雖然我都是必須要在 commit() 之前執行會取到數值，但是有些人會在 commit() 之後才取到。
                // 所以我還是用 select 來取出來好了。
                $output = $this->conn->selectTransact( 'SELECT id FROM member WHERE email = ?', [ $email ]);
                if ( is_int( $output ) ) {
                    return $output;
                } else if ( count( $output ) !== 1 ) {
                    return static::PROC_SERV_ERROR;
                }
                $output = [
                    'mem_id' => $output[0]['id']
                ];
            } else {
                $output = static::PROC_DUPLICATE;
            }
        }
        return $output;
    }

    /**
     * Reset account password, and you will get a payload with a new random password.
     *
     * NOTE: Only the activate account and initializing account can reset password.
     *       However, there are some different between 1 and 3. Status 1 works for password-forget
     *       and account password changing, and status 3 works for a member account first reset.
     *
     * NOTE: It will make ex-auth-token not working in database of this member.
     *
     * @param string $email
     * @param int $scope The value depends on member status (1 = activate, 2 = block, 3 = initial).
     *                   When the initPw() method called, it makes system to
     *                   check up different status on member for password renew.
     * @return array|int It will return a payload array on success, or an error integer code
     */
    public function resetPw ( string $email, int $scope = 1 )
    {
        // characters filter
        $email = filter_var( trim( $email ), FILTER_SANITIZE_EMAIL );
        if( !ValueValidate::is_email( $email )  ) {
            return static::PROC_INVALID;
        } else if ( $scope !== 1 && $scope !== 3 ) {// $scope <= 0 || $scope === 2 || $scope > 3 )
            // prevent user use scope(member type) in 2.
            return static::PROC_INVALID;
        }

        $out = $this->conn->selectTransact(
            'SELECT m.id, MAX( p.create_on ) 
             FROM member AS m 
             LEFT JOIN pw_reset_buf AS p ON p.mem_id = m.id
             WHERE m.email = ? AND m.status = ? 
             GROUP BY m.id
             HAVING MAX( p.create_on ) < DATE_SUB( NOW(), INTERVAL 10 SECOND )
                 OR MAX( p.create_on ) IS NULL',
            [ $email, $scope ]
        );
        if ( is_int( $out ) ) {
            return $out;
        } else if ( count( $out ) !== 1 ) {
            return static::PROC_SERV_ERROR;
        }
        $row = $out[0];

        $out  = static::PROC_FAIL;
        $stmt = NULL;
        try {
            $this->conn->pdo->beginTransaction();
            // NOTE: The value depends on member status (1 = activate, 2 = block, 3 = initial).
            //       When the initPw() method called, it makes system to
            //       check up different status on member for password renew.
            $payload = JwtPayload::genPayload( 
                $this->settings['oauth']['issuer'], 
                Globals::INVITATION_EXP_TIME,
                [ 'scope' => $scope ]
            ); // expired after 7 days.

            // remove others first, but new one.
            // it will make ex-reset url not work.
            $stmt = $this->conn->pdo->query( 'DELETE FROM pw_reset_buf WHERE mem_id = ' . $row['id'] );
            if ( $stmt !== false ) {
                // after all, insert a new one.
                $stmt = $this->conn->pdo->prepare(
                    'INSERT INTO pw_reset_buf ( rand_uid, expired_on, mem_id )
                     VALUES ( ?, ' . $payload['exp'] . ', ' . $row['id'] . ')' 
                );
                if ( $stmt->execute( [ $payload['jti'] ] ) === false || $stmt->rowCount() === 0 ) {
                    $payload = false;
                } // else: keep the payload array for output to be a jwt token.
            } else {
                $payload = false;
            }
            // run final
            if ( $payload === false ) {
                $this->conn->pdo->rollBack();
            } else {
                $this->conn->pdo->commit();
                $out = $payload;    // output the array of payload
            }
        } catch ( Exception $e ) {
            $out = $this->conn->sqlExceptionProc( $e );
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $out;
    }
    
    /**
     * Working for forget-password and password-reset.
     * 
     * @param string $newPW
     * @param string $verif_code it is a jwt jti random code string.
     * @param int $scope
     * @return array|int It will get an email, or false on failure.
     */
    public function initPw ( string $newPW, string $verif_code, int $scope = 1 )
    {
        // check format each field.
        if ( !ValueValidate::is_sha512( $newPW ) ) {
            return static::PROC_INVALID;
        } else if ( empty( $verif_code ) ) {
            return static::PROC_INVALID;
        } else if ( $scope < 1 || $scope > 3 ) {
            return static::PROC_INVALID;
        }

        $output = static::PROC_FAIL;
        $stmt   = NULL;
        try {
            $stmt = $this->conn->pdo->prepare( 
                'SELECT p.mem_id, m.email 
                 FROM pw_reset_buf AS p 
                 INNER JOIN member AS m ON m.id = p.mem_id
                 WHERE FROM_UNIXTIME( p.expired_on ) > NOW() 
                   AND p.rand_uid = ? AND m.status = ' . $scope );
            if ( $stmt->execute( array( $verif_code ) ) && $stmt->rowCount() === 1 ) {
                $row = $stmt->fetch();
                // NOTE: don't forget to use password_hash( password, PASSWORD_BCRYPT )
                $newPW = password_hash( $newPW, PASSWORD_BCRYPT );

                $this->conn->pdo->beginTransaction();
                // you must delete reset buffer first to stop reset by others in the moment of password changing.
                $stmt = $this->conn->pdo->prepare( 
                    'DELETE FROM pw_reset_buf WHERE rand_uid = ? AND mem_id = ' . $row['mem_id'] 
                );
                if ( $stmt->execute( array( $verif_code ) ) ) {
                    // update to the new password of member.
                    // NOTE: when the member is initializing account, the status code will be turned to 1 from 3.
                    $stmt = $this->conn->pdo->prepare(
                        'UPDATE member SET pw = ?' . ( $scope === 3 ? ', status = 1 ' : ' ' ) .
                        'WHERE status = ' . $scope . ' AND id = ' . $row['mem_id']
                    );
                    if ( $stmt->execute( array( $newPW ) ) ) {
                        // remove all access token and api token about the member account.
                        $is_done = $this->conn->pdo->exec( 
                            'DELETE FROM oauth_access WHERE user_id = ' . $row['mem_id']
                        );
                        if ( $is_done !== false ) {
                            $is_done = $this->conn->pdo->exec(
                                'DELETE FROM oauth_api WHERE user_id = ' . $row['mem_id']
                            );
                            if ( $is_done !== false ) {
                                $output = [
                                    'email' => $row['email']
                                ];
                            }
                        }
                    }
                }
                // commit or not
                if ( is_int($output) ) {
                    $this->conn->pdo->rollBack();
                } else {
                    $this->conn->pdo->commit();
                }
            }
        } catch ( Exception $e ) { //PDOException
            $output = $this->conn->sqlExceptionProc($e);
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $output;
    }
    
    /**
     * Modify user password.
     *
     * NOTE: It needs to clean all auth token about this member at app side database
     * after the function called.
     *
     * There are two-way to modify member account password:
     * 1. from outside the web page. E.g. forget-password
     * 2. from inside of management page. E.g. users management page to modify a specified password,
     *    or I suggest you to reset password(generate random password) is better.
     *
     * @param int $userID
     * @param string $newPW A new password you want to replace.
     * @param string $exPW It is an ex-password
     * in database after password reset. So you need it to double-check the processing is accessed.
     * @return array|int It will get an email, or false on failure.
     */
    public function changePw ( int $userID, string $newPW, string $exPW )
    {
        // check format each field.
        if ( $userID <= 0 ) {
            return static::PROC_INVALID;
        } else if ( strlen( $newPW ) != StrProc::SHA512_PW_LEN ) {
            return static::PROC_INVALID;
        } else if ( strlen( $exPW ) != StrProc::SHA512_PW_LEN ) {
            return static::PROC_INVALID;
        }
        
        $output = static::PROC_FAIL;
        $stmt = NULL;
        try {
            $stmt = $this->conn->pdo->query( 
                'SELECT email, pw FROM member WHERE status = 1 AND id =' . $userID 
            );
            if ( $stmt !== false && $stmt->rowCount() === 1 ) {
                $row = $stmt->fetch();
                
                if ( password_verify( $exPW, $row['pw'] ) ) {
                    $this->conn->pdo->beginTransaction();
                    // NOTE: Do not forget to use password_hash( password, PASSWORD_BCRYPT ) method,
                    //       and then save password to database.
                    $newPW = password_hash( $newPW, PASSWORD_BCRYPT );
                    $stmt = $this->conn->pdo->prepare(
                        'UPDATE member SET pw = ?
                         WHERE status = 1 AND id = ' . $userID );
                    if ( $stmt->execute( array( $newPW ) ) ) {
                        // remove all access token and api token about the member account.
                        $is_done = $this->conn->pdo->exec(
                            'DELETE FROM oauth_access WHERE user_id = ' . $userID 
                        );
                        if ( $is_done !== false ) {
                            $is_done = $this->conn->pdo->exec(
                                'DELETE FROM oauth_api WHERE user_id = ' . $userID 
                            );
                            if ( $is_done !== false ) {
                                $output = [
                                    'email' => $row['email']
                                ];
                            }
                        }
                    } 
                    // commit or not
                    if ( is_int($output) ) {
                        $this->conn->pdo->rollBack();
                    } else {
                        $this->conn->pdo->commit();
                    }
                }
            }
        } catch ( Exception $e ) { //PDOException
            $output = $this->conn->sqlExceptionProc($e);
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $output;
    }

    /**
     * 如同 genAccessJwt() 檢查帳號密碼，但是後續卻是與其不相同
     *
     * IMPORTANT: 請於 Ctrl 部份，就先進行 user password 的檢查，才可以使用這個 function
     *
     * @param string $email
     * @return int|array
     * @throws Exception
     */
    public function OTP_generator( string $email )
    {
        $email = filter_var( trim( $email ), FILTER_SANITIZE_EMAIL );
        if( !preg_match( StrProc::REGEX_EMAIL_CHAR, $email ) ) {
            return static::PROC_INVALID;
        }
        // only working for the user is status = 1
        $out = $this->conn->selectTransact(
            'SELECT a.id, a.company_id, a.otp_flg, IF( (b.status != 1 OR a.status != 1), 1, 0 ) AS block
            FROM member AS a
            INNER JOIN company AS b ON b.id = a.company_id
            WHERE a.email = ?',
            [ $email ]
        );
        if ( is_int( $out ) ) {
            return $out;
        }
        $row_num = count( $out );
        if ( $row_num > 1 ) {                   // database unique ID is crash
            return static::PROC_SERV_ERROR;
        } else if ( $row_num === 0 ) {          // there is no such man
            return static::PROC_FAIL;
        } else if ( $out[0]['block'] === 1 ) {  // the user is disabled
            return static::PROC_BLOCKED;
        }

        $out = $out[0];
        if ( $out['otp_flg'] >= 1 ) {  // user have to use OTP
            $mem_id      = $out['id'];
            $mem_company = $out['company_id'];
            $rand_uuid   = Uuid::v4();
            $otp_code    = GnRandom::genSalt(6); // 6-char OTP code
            $bcrypt      = password_hash( $otp_code, PASSWORD_BCRYPT );    // 資料庫留的不可以是明碼
            if ( is_null( $bcrypt ) || $bcrypt === false ) {
                return static::PROC_SERV_ERROR;
            }
            $num       = 0;
            $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
            $usr_agent = substr( ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 );
            $out = $this->conn->writeTransact(
                'INSERT INTO oauth_otp( uuid, mem_id, company_id, otp_code, ip_address, channel, device_info )
                SELECT ?, ?, ?, ?, ?, ?, ? 
                FROM DUAL WHERE NOT EXISTS ( 
                    SELECT uuid, MAX( create_on ) FROM oauth_otp
                    WHERE is_used = 0 AND ( ip_address = ? OR device_info = ? ) AND mem_id = ?
                    GROUP BY uuid
                    HAVING MAX( create_on ) >= DATE_SUB( NOW(), INTERVAL 10 SECOND ) OR MAX( create_on ) IS NULL 
                )',
                [
                    $rand_uuid,
                    $mem_id,
                    $mem_company,
                    $bcrypt,
                    $ip,
                    'login',
                    $usr_agent,
                    $ip,
                    $usr_agent,
                    $mem_id
                ], $num
            );
            // if there is no row effected, it is still fail. because it is not happened!!
            if ( $out === static::PROC_OK && $num === 1 ) {
                // clear ex-code, and don't care about it is success or not
                $is_clear = $this->conn->writeTransact(
                    'DELETE FROM oauth_otp 
                    WHERE mem_id = ? AND ( is_used != 0 OR create_on < DATE_SUB( NOW(), INTERVAL 5 MINUTE ) )',
                    [ $mem_id ]
                );
                return [
                    'uuid'       => $rand_uuid,
                    'mem_id'     => $mem_id,
                    'company_id' => $mem_company,
                    'otp_code'   => $otp_code,       // 記得回傳要是明碼
                    'del_code'   => $is_clear        // to let ctrl-side to know what's wrong in delete process
                ];
            } else {
                return static::PROC_FAIL;
            }
        } else {    // don't have to use OTP
            return [];
        }
    }

    /**
     * to check the OTP 6-char is correct
     *
     * @param string $uuid
     * @param string $otp
     * @return int|array
     * @throws Exception
     */
    public function verifyOTP( string $uuid, string $otp )
    {
        if ( !Uuid::is_valid( $uuid ) || !preg_match( StrProc::REGEX_OTP_6CHAR, $otp ) ) {
            return static::PROC_INVALID;
        }

        $out = $this->conn->selectTransact(
            'SELECT a.mem_id, a.otp_code, a.attempts, a.device_info,
                b.pw, b.email, 
                IF( (b.status != 1 OR c.status != 1), 1, 0 ) AS block
            FROM oauth_otp AS a 
            INNER JOIN member AS b ON b.id = a.mem_id
            INNER JOIN company AS c ON c.id = b.company_id
            WHERE a.uuid = ? AND 
                  a.is_used = 0 AND 
                  a.create_on >= DATE_SUB( NOW(), INTERVAL 5 MINUTE )',
            [ $uuid ]
        );
        if ( is_int( $out ) ) {
            return $out;
        }
        $row_num = count( $out );
        if ( $row_num > 1 ) {
            return static::PROC_SERV_ERROR;
        } else if ( $row_num === 0 ) {   // may be not existed or expired
            return static::PROC_FAIL;
        } else if ( $out[0]['block'] === 1 ) {
            // if the user is blocked, close the OTP, and remove it from database
            $this->conn->writeTransact( 'DELETE FROM oauth_otp WHERE uuid = ?', [ $uuid ] );
            return static::PROC_BLOCKED;
        }

        $memRow    = $out[0];
        $attempt   = $memRow['attempts'];
        $usr_agent = substr( ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 );
        if ( password_verify( $otp, $memRow['otp_code'] ) && $usr_agent === $memRow['device_info'] ) {
            $out = $this->conn->writeTransact( 'UPDATE oauth_otp SET is_used = 1 WHERE uuid = ?', [ $uuid ] );
            if ( $out !== static::PROC_OK ) {
                return $out;
            }

            // OTP 驗證過後，一律通通以「remember me = 1」有效期限發予 access token
            // create a new payload array
            $payload = JwtPayload::genPayload( 
                $this->settings['oauth']['issuer'], 
                Globals::ACCESS_TIME_REMEMBER_ME
            );
            // a new auth code
            $authcode = JwtPayload::genAuthCode(
                $memRow['pw'] . ( Globals::ACCESS_WITH_BROWERAGENT ? $_SERVER['HTTP_USER_AGENT'] : '' )
            );
            // insert access token.
            $out = $this->conn->writeTransact(
                'INSERT INTO oauth_access ( id, user_id, auth_code, exp_time ) 
                 VALUES( ?, ?, ?, FROM_UNIXTIME( ? ) )', [
                    $payload['jti'],
                    $memRow['mem_id'],
                    $authcode,
                    $payload['exp']
                ]
            );
            if ( $out === static::PROC_OK ) {
                // delete all oauth is expired about the user
                $this->conn->writeTransact(
                    'DELETE FROM oauth_access 
                    WHERE exp_time <= NOW() AND user_id =' . $memRow['mem_id']
                );
                return [
                    'email'   => $memRow['email'],
                    'payload' => $payload
                ];
            }
            return $out;
        } else {
            // 最多一組驗證只可以用三次。如果錯誤了三次，就做廢。
            $attempt += 1;
            $out = $this->conn->writeTransact(
                'UPDATE oauth_otp 
                SET attempts = ' . $attempt . ', is_used = '. ( $attempt >= 3 ? 1 : 0 ) .
                ' WHERE uuid = ?', [ $uuid ] );
            if ( $attempt >= 3 && $out === static::PROC_OK ){
                return static::PROC_EXCEEDED_ATTEMPT;
            }
            if ( $out !== static::PROC_OK ) {
                return $out;
            }
            return static::PROC_FAIL; // 畢竟這邊是屬於錯誤的處理區域，所以即便是更新的attempts，但是還是要回應此程序錯誤
        }
    }

    /**
     * to change a new OTP 6-char code for user for log-in in OTP
     *
     * @param string $uuid
     * @return array|int
     */
    public function resentOTP ( string $uuid )
    {
        if ( !Uuid::is_valid( $uuid ) ) {
            return static::PROC_INVALID;
        }
        
        // check the uuid is valid, user is grant for access, and not expired(not over 5 minutes)
        $data = $this->conn->selectTransact(
            'SELECT a.is_used, a.ip_address, a.attempts, a.channel, a.device_info,
                IF( ( a.create_on < DATE_SUB( NOW(), INTERVAL 5 MINUTE ) ), 1, 0 ) AS is_exp,
                b.email, a.mem_id, a.company_id
            FROM oauth_otp AS a
            INNER JOIN member AS b ON b.id = a.mem_id
            INNER JOIN company AS c ON c.id = b.company_id
            WHERE a.uuid = ? AND 
                  a.is_used = 0 AND
                  a.device_info = ? AND
                  b.status = 1 AND 
                  c.status = 1',
            [ $uuid, $_SERVER['HTTP_USER_AGENT'] ]
        );
        if ( is_int( $data ) ) {
            return $data;
        }
        $row_num = count( $data );
        if ( $row_num > 1 ) {
            return static::PROC_SERV_ERROR;
        } else if ( $row_num === 0 ) {
            return static::PROC_FAIL;
        } else if ( $data[0]['is_exp'] === 1 ) {
            return static::PROC_FAIL;       // 暫時故意分出來，方便debug用
        }

        // re-create a new code for the uuid
        $data = $data[0];
        $otp_code = GnRandom::genSalt(6);
        $bcrypt   = password_hash( $otp_code, PASSWORD_BCRYPT );    // 資料庫留的不可以是明碼
        if ( is_null( $bcrypt ) || $bcrypt === false ) {
            return static::PROC_SERV_ERROR;
        }
        $num       = 0;
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
        $usr_agent = substr( ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 );
        $out = $this->conn->writeTransact(
            'UPDATE oauth_otp SET otp_code = ?, ip_address = ?, attempts = 0
             WHERE uuid = ? AND is_used = 0 AND company_id = ? AND mem_id = ? AND device_info = ?',
            [
                $bcrypt,
                $ip,
                $uuid,
                $data['company_id'],
                $data['mem_id'],
                $usr_agent
            ], $num
        );
        // if there is no row effected, it is still fail. because it is not happened!!
        if ( $out === static::PROC_OK && $num === 1 ) {
            return [
                'email' => $data['email'],
                'code'  => $otp_code
            ];
        } else {
            return static::PROC_FAIL;
        }
    }

    /**
     * When ID and PW is accessed, system must insert a data to
     * oauth_access with JWT information data.
     * <p>Don't forget to check the SQL injection attack(PDO).</p>
     *
     * NOTE: Only the member status is 1 and the member's company status is 1 can get access token.
     *
     * @param string $username
     * @param string $password
     * @param bool   $remember_me If TRUE, give 2-year lifetime, or 24h default
     * @return array|int          If it is success it will return a token payload array, or you will get error number code.
     */
    public function genAccessJwt ( string $username, string $password, bool $remember_me = false )
    {
        // characters filter
        $username = filter_var( trim( $username ), FILTER_SANITIZE_EMAIL );
        // check format each field.
        if( !preg_match( StrProc::REGEX_EMAIL_CHAR, $username ) ) {
            return static::PROC_INVALID;
        } else if ( strlen( $password ) !== StrProc::SHA512_PW_LEN ) {
            return static::PROC_INVALID;
        }
        
        $result = static::PROC_FAIL;
        $stmt   = NULL;
        try {
            // if company is disabled, no one can be accessed to in this company.
            $stmt = $this->conn->pdo->prepare( 
                'SELECT m.id, m.pw, m.status 
                 FROM member AS m 
                 INNER JOIN company AS c ON c.id = m.company_id 
                 WHERE m.email = ? AND c.status = 1' );
            if ( $stmt->execute( array( $username ) ) && $stmt->rowCount() === 1 ) {
                // it will get only one row because [username] is unique.
                $memRow = $stmt->fetch();

                // only the member status is 1 can be access.
                switch( $memRow['status'] ) {
                    case 1:
                        if ( password_verify( $password, $memRow['pw'] ) ) {
                            $exp_time_str = $remember_me ? Globals::ACCESS_TIME_REMEMBER_ME : Globals::ACCESS_TIME_UNREMEMBER_ME;
                            // create a new payload array
                            $payload = JwtPayload::genPayload( 
                                $this->settings['oauth']['issuer'],
                                $exp_time_str 
                            );
                            // a new auth code
                            $authcode = JwtPayload::genAuthCode( 
                                $memRow['pw'] . ( Globals::ACCESS_WITH_BROWERAGENT ? $_SERVER['HTTP_USER_AGENT'] : '' ) 
                            );
                            // sql values
                            $sqlVal = [ 
                                $payload['jti'], 
                                $memRow['id'], 
                                $authcode, 
                                $payload['exp'] 
                            ];
                            // for database table in InnoDB engine.
                            $this->conn->pdo->beginTransaction();
                            // insert access token.
                            $stmt = $this->conn->pdo->prepare(
                                'INSERT INTO oauth_access (id, user_id, auth_code, exp_time) 
                                 VALUES(?, ?, ?, FROM_UNIXTIME(?))' 
                            );
                            if ( $stmt->execute( $sqlVal ) ) {
                                // 10% chance to delete expired access token.
                                if ( rand( 0, 9 ) === 1 ) {
                                    $this->conn->pdo->query(
                                        'DELETE FROM oauth_access 
                                         WHERE exp_time <= NOW() AND user_id =' . 
                                        $memRow['id'] );
                                }
                                $this->conn->pdo->commit();
                                $result = $payload;
                            } else {
                                $this->conn->pdo->rollBack();
                                $result = static::PROC_TOKEN_ERROR;
                            }
                        }
                        break;
                    case 2: // block
                        $result = static::PROC_BLOCKED;
                        break;
                    case 3: // initial
                        $result = static::PROC_UNINITIALIZED;
                        break;
                }
            }
        } catch ( Exception $e ) {
            $result = $this->conn->sqlExceptionProc( $e );
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $result;
    }

    /**
     * Check the password sent is legal or not.
     *
     * @param int|string $user
     * @param string $pw_sha512
     * @param bool $is_email    when it is true, the $member_id is string of SN; Otherwise, it is int of member ID.
     * @return int
     */
    public function checkUserPw ( $user, string $pw_sha512, bool $is_email = false ): int
    {
        $user = ( is_string( $user ) && $is_email ) ? filter_var( trim( $user ), FILTER_SANITIZE_EMAIL ) : (int)$user;
        if ( !ValueValidate::is_sha512( $pw_sha512 ) ) {
            return static::PROC_INVALID;
        } else if ( !$is_email && $user <= 0 ) {
            return static::PROC_INVALID;
        } else if ( $is_email && !ValueValidate::is_email( $user ) ) {
            return static::PROC_INVALID;
        }
        // if company is disabled, no one can be accessed to in this company.
        $out = $this->conn->selectTransact(
            'SELECT m.pw, m.status 
             FROM member AS m 
             INNER JOIN company AS c ON c.id = m.company_id 
             WHERE ' . ( $is_email ? 'm.email' : 'm.id' ) . ' = ? AND c.status = 1',
            [ $user ]
        );
        if ( is_int( $out ) ) {
            return $out;
        } else if ( count( $out ) !== 1 ) {
            return static::PROC_SERV_ERROR;
        }
        $out = $out[0];
        switch( $out['status'] ) {
            case 0: // account has existed, but now it is removed, maybe one day the man will come back
                return static::PROC_FAIL;
            case 1:
                if ( password_verify( $pw_sha512, $out['pw'] ) ) {
                    return static::PROC_OK;
                }
                return static::PROC_FAIL;
            case 2: // block
                return static::PROC_BLOCKED;
            case 3: // initial
                return static::PROC_UNINITIALIZED;
            default:
                return static::PROC_SERV_ERROR;
        }
    }
    
    /**
     * Check the access token payload is accessed or not.
     *
     * @param string $access_jti
     * @return bool|array Return member id and company if it is success.
     */
    public function isAccessJwt ( string $access_jti )
    {
        if ( empty( $access_jti ) ) {
            return static::PROC_INVALID;
        }
        
        $out  = static::PROC_FAIL;
        $stmt = NULL;
        try {
            // if company is disabled, no one can be accessed to in this company.
            $stmt = $this->conn->pdo->prepare(
                'SELECT a.user_id, m.company_id, a.auth_code, m.pw 
                 FROM oauth_access AS a 
                 INNER JOIN member AS m ON m.id = a.user_id 
                 INNER JOIN company AS c ON c.id = m.company_id 
                 WHERE a.id = ? AND c.status = 1 AND m.status = 1 AND a.exp_time > NOW()' );
            if ( $stmt->execute( array( $access_jti ) ) && $stmt->rowCount() === 1 ) {
                $row = $stmt->fetch();
                $auth_code = JwtPayload::genAuthCode( 
                    $row['pw'] . ( Globals::ACCESS_WITH_BROWERAGENT ? $_SERVER['HTTP_USER_AGENT'] : '' ) 
                );
                // check the auth code is matched or not.
                if ( hash_equals( $row['auth_code'], $auth_code ) ) {
                    // output user id, user type, and user permission sheet
                    $out = [
                        'id'      => $row['user_id'],
                        'company' => $row['company_id']
                    ];
                }
            }
        } catch ( Exception $e ) {
            $out = $this->conn->sqlExceptionProc( $e );
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $out;
    }
    
    /**
     * function is like a log-out for application.
     *
     * @param string $access_jti
     * @return int user id or false on failure.
     */
    public function removeAccessByJti ( string $access_jti ): int
    {
        if ( empty( $access_jti ) ) {
            return static::PROC_INVALID;
        }
        $result = static::PROC_FAIL;
        $stmt   = NULL;
        try {
            $this->conn->pdo->beginTransaction();
            // remove the access token you prepare to log out
            $stmt = $this->conn->pdo->prepare( 'DELETE FROM oauth_access WHERE id = ?' );
            if ( $stmt->execute( [ $access_jti ] ) ) {
                $this->conn->pdo->commit();
                $result = static::PROC_OK;
            } else {
                $this->conn->pdo->rollBack();
            }
        } catch ( Exception $e ) {
            $this->conn->sqlExceptionProc( $e );
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $result;
    }
    
    /**
     * P.S. Before the function called, use isAccessJwt() first for access token is legal or not.
     *
     * @param int $user_id
     * @param string $access_jti
     * @return bool|array
     */
    public function genApiJwt ( int $user_id, string $access_jti )
    {
        if ( empty( $access_jti ) || $user_id <= 0 ) {
            return false;
        }
        
        $out  = false;
        $stmt = NULL;
        try {
            // new a payload, and let jwt middleware decoder know where does it come from.
            $payload = JwtPayload::genPayload( 
                $this->settings['oauth']['issuer'],
                Globals::ACCESS_TIME_API_DEFAULT,
                ['uid' => $user_id], // add user id to payload,
                '',
                JwtPayload::PAYLOAD_AUTH_CHANNEL_USR
            );
            // use access token jti to create an auth_code.
            // It is useful to find out the api token is belonged to which access token
            $access_code = JwtPayload::genAuthCode( $access_jti );
            
            $this->conn->pdo->beginTransaction();
            $stmt = $this->conn->pdo->prepare(
                'INSERT INTO oauth_api (id, user_id, access_id, exp_time) 
                 VALUES(?, ?, ?, FROM_UNIXTIME(?))' );
            if ( $stmt->execute( [ $payload['jti'], $user_id, $access_code, $payload['exp'] ] ) ) {
                // 10% chance to delete expired token.
                if ( rand( 0, 9 ) === 1 ) {
                    $this->conn->pdo->query(
                        'DELETE FROM oauth_api 
                         WHERE user_id =' . $user_id . ' AND exp_time <= NOW()');
                }
                $this->conn->pdo->commit();
                $out = $payload;
            } else {
                $this->conn->pdo->rollBack();
            }
        } catch ( Exception $e ) {
            $this->conn->sqlExceptionProc( $e );
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $out;
    }

    /**
     * Detect the jwt is real or not.
     *
     * NOTE 2023-08-25: 除了 permission 之外，加上輸出 network 和 stage 的資料在其中，以利以後權限劃分的使用
     *
     * @param string $jti From web api token (not access token jti)
     * @param array  $permissionFlg
     * @return boolean|array
     */
    public function isApiJwt ( string $jti, array $permissionFlg = [] )
    {
        if ( empty( $jti ) ) {
            return false;
        }
        
        // 只要你的公司以及會員帳號不是 activate 就不可以通過驗證
        $out = $this->conn->selectTransact(
            'SELECT a.user_id, m.company_id
             FROM oauth_api AS a
             INNER JOIN member AS m ON m.id = a.user_id
             INNER JOIN company AS c ON c.id = m.company_id
             WHERE a.id = ? AND c.status = 1 AND m.status = 1 AND m.type > 0 AND a.exp_time > NOW()',
            array( $jti ) );
        if ( is_int( $out ) || empty( $out ) ) {
            return false;
        }
        
        // NOTE @2023-11-22 by Nick: 先確認 jwt 的 code 是否合法，然後其他的 member 資訊，就由 self::getMemInfo()這個函逝去解決就好了。
        //      廢除以前的方式，並且 network & stage 由 Ctrl 的部份做加載，保持這邊的乾淨
        $mem_id     = $out[0]['user_id'];
        $company_id = $out[0]['company_id'];
        $out = self::getMemInfo( $company_id, $mem_id, true );
        if ( $out === false ) {
            return false;
        }
        $result = [
            'id'         => $mem_id,
            'company'    => $company_id,
            'plan'       => $out['plan'],
            'type'       => $out['type'],
            'status'     => $out['status'],
            'permission' => $out['permission']
        ];
        
        // 如果有指定的權限檢查，則該項權限有核可，就立刻放行 return 結果
        if ( !empty( $permissionFlg ) ) {
            $is_access = false;
            foreach ( $result['permission'] as $k => $v ) {
                if ( isset( $permissionFlg[ $k ] ) && $v >= $permissionFlg[ $k ] ) {
                    $is_access = true;
                    break;
                }
            }
            if ( !$is_access ) {    // 但是如果全部 loop 檢查後都沒有符合的結果，則該檢查代表權限不足
                return false;
            }
        }
        return $result;
    }
    
    /**
     * Remove api jwt in oauth_api table.
     *
     * @param string $access_jti From JWT jti value.
     * @return boolean
     */
    public function removeApiJwtByAccess( string $access_jti ): bool
    {
        if ( empty( $access_jti ) ) {
            return static::PROC_INVALID;
        }
        
        $result = static::PROC_FAIL;
        $stmt   = NULL;
        try {
            $access_code = JwtPayload::genAuthCode( $access_jti );
            $this->conn->pdo->beginTransaction();
            $stmt = $this->conn->pdo->prepare( 'DELETE FROM oauth_api WHERE access_id = ?' );
            if( $stmt->execute( [ $access_code ] ) ) {
                $this->conn->pdo->commit();
                $result = static::PROC_OK;
            } else {
                $this->conn->pdo->rollBack();
            }
        } catch ( Exception $e ) {
            $this->conn->sqlExceptionProc( $e );
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $result;
    }

    /**
     * Get member account information.
     * 
     * If you don't like the role permissions are base on each columns of table, you can use JSON to save them or other way you are used to 
     *
     * @param int $company
     * @param int $id
     * @param bool $perms If you need to get member permission information, or return empty array for permission element in array.
     * @return boolean|array
     */
    public function getMemInfo ( 
        int $company, 
        int $id, 
        bool $perms = false
    ) {
        if ( $company <= 0 || $id <= 0 ) {
            return false;
        }
        // to get general member information
        $out = $this->conn->selectTransact(
            'SELECT m.email,
                    m.otp_flg,
                    m.status,
                    m.type,
                    m.nickname,
                    m.msg,
                    c.plan_id,
                    g.name AS role_name,
                    g.id AS role_id
            FROM member AS m
            INNER JOIN company AS c ON c.id = m.company_id
            INNER JOIN roles AS g ON g.id = m.role_id
            WHERE m.company_id = ' . $company . ' AND m.id =' . $id 
        );
        if ( is_int( $out ) || empty( $out ) ) {    // permission can not be empty. if it is, it is error!
            return false;
        }
        $result = [
            'id'         => $id,
            'company'    => $company,
            'plan'       => $out[0]['plan_id'],
            'email'      => $out[0]['email'],
            'status'     => $out[0]['status'],
            'type'       => $out[0]['type'],
            'name'       => $out[0]['nickname'],
            'msg'        => $out[0]['msg'],
            'otp_flg'    => $out[0]['otp_flg'],
            'role'       => $out[0]['role_name'],
            'role_id'    => $out[0]['role_id'],
            'permission' => []
        ];
        
        // to get permissions
        if ( $perms ) {
            $out = $this->conn->selectTransact( 
                'SELECT * FROM roles_properties WHERE role_id = ' . $out[0]['role_id']
            );
            // permission can not be empty. if it is, it is error!
            if ( is_int( $out ) || empty( $out ) ) {
                return false;
            }
            unset( $out[0]['role_id'] );
            unset( $out[0]['modify_on'] );
            $result['permission'] = $out[0];    // add value to permission column.
        }
        return $result;
    }

    /**
     * update member type, except super admin.
     * client side don't edit super admin. it can be changed only in backend side.
     *
     * @param int $company_id
     * @param int $user_id
     * @param int $type
     * @return int
     */
    public function setMemberType ( int $company_id, int $user_id, int $type ): int
    {
        if ( $company_id <= 0 || $user_id <= 0 ) {
            return static::PROC_INVALID;
        } else if ( $type <= 1 || $type > 3 ) { // only you can be the admin(2) and member(3).
            return static::PROC_INVALID;
        }
        return $this->conn->writeTransact(
            'UPDATE member SET type = ' . $type . 
            ' WHERE id = ' . $user_id . ' AND company_id = ' . $company_id
        );
    }
    
    /**
     * 
     * @param int $company_id
     * @param int $mem_id
     * @param int $status_code 0 is removed, 1 is activated, 2 is blocked, 3 is initializing.
     * @return int
     */
    public function modifyMemberStatus ( int $company_id, int $mem_id, int $status_code ): int
    {
        if ( $company_id <= 0 || $mem_id <= 0 ) {
            return static::PROC_INVALID;
        } else if ( $status_code < 0 || $status_code > 3 ) {
            return static::PROC_INVALID;
        }
        
        $result = static::PROC_FAIL;
        $stmt   = NULL;
        try {
            $this->conn->pdo->beginTransaction();
            // change status without super admin, so type must > 1.
            // IMPORTANT: Can not change member status to 1|2 from 3 (initializing).
            $is_done = false;
            $stmt = $this->conn->pdo->prepare( 
                'UPDATE member SET status = ? WHERE type > 1 AND status != 3 AND id = ? AND company_id = ?' 
            );
            if ( $stmt->execute( [ $status_code, $mem_id, $company_id ] ) ) {
                $is_done = true;
                // remove all token authorities if the status is not in activate.
                if ( $status_code !== 1 ) {
                    $is_done = $this->conn->pdo->exec( 'DELETE FROM oauth_access WHERE user_id = ' . $mem_id );
                    if ( $is_done !== false ) {
                        $is_done = $this->conn->pdo->exec( 'DELETE FROM oauth_api WHERE user_id = ' . $mem_id );
                    }
                }
            }
            if ( $is_done === false ) {
                $this->conn->pdo->rollBack();
            } else {
                $this->conn->pdo->commit();
                $result = self::PROC_OK;
            }
        } catch ( Exception $e ) { //PDOException
            $result = $this->conn->sqlExceptionProc( $e );
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $result;
    }
    
    /**
     * Set member role and ensure the role belongs to the company of member.
     * Don't allow to set any value in 0(zero) in this function.
     *
     * @param int $company_id
     * @param int $user_id
     * @param int $role_id
     * @return int
     */
    public function setMemberRole ( int $company_id, int $user_id, int $role_id ): int
    {
        if ( $company_id <= 0 || $user_id <= 0 || $role_id <= 0 ) {
            return static::PROC_INVALID;
        }
        $result = static::PROC_FAIL;
        try {
            $this->conn->pdo->beginTransaction();
            // IMPORTANT: prevent insert other company role id, and you cannot assign role ID with company_id = 0.
            // Only the company/group owner can have plan default permissions( the one with company_id = 0 ),
            // any other member with customized role id with each other company_id.
            // It can protect the super admin is in the highest level in the company/group.
            // And if the company plan is changed to other level, all role permissions will be modified again to keep
            // all roles of the company are not over the super admin has.
            $is_done = $this->conn->pdo->exec(
                'UPDATE member SET role_id = '. $role_id . 
                ' WHERE id = ' . $user_id . 
                ' AND ( SELECT company_id FROM roles WHERE id = ' . $role_id . ' ) = ' . $company_id 
            );
            if ( $is_done !== false ) {
                if ( $is_done > 0 ) { // if the role is changed, you must make the member re-log in again.
                    // remove all access token and api token about the member account.
                    $is_done = $this->conn->pdo->exec( 'DELETE FROM oauth_access WHERE user_id = ' . $user_id );
                    if ( $is_done !== false ) {
                        $is_done = $this->conn->pdo->exec( 'DELETE FROM oauth_api WHERE user_id = ' . $user_id );
                        if ( $is_done !== false ) {
                            $this->conn->pdo->commit();
                            $result = self::PROC_OK;
                        } else {
                            $this->conn->pdo->rollBack();
                        }
                    } else {
                        $this->conn->pdo->rollBack();
                    }
                } else {
                    $this->conn->pdo->commit();
                    $result = self::PROC_OK;
                }
            } else {
                $this->conn->pdo->rollBack();
            }
        } catch ( Exception $e ) {
            $result = $this->conn->sqlExceptionProc( $e );
        }
        return $result;
    }

    /**
     * Change profile of user.
     *
     * @param int $company_id
     * @param int $user_id
     * @param string $name
     * @param string $msg
     * @param bool $otp
     * @return int
     */
    public function setMemberProfile ( int $company_id, int $user_id, string $name, string $msg, bool $otp ): int
    {
        $name = trim( $name ); // allow anything in string, event empty string
        $msg  = trim( $msg );  // allow anything in string, event empty string
        if ( $user_id <= 0 || $company_id <= 0 ) {
            return static::PROC_INVALID;
        } else if ( !empty( $name ) && ValueValidate::safeStrlen( $name ) > 64 ) {
            return static::PROC_INVALID;
        } else if ( !empty( $msg ) && ValueValidate::safeStrlen( $msg ) > 128 ) {
            return static::PROC_INVALID;
        }
        $sql = 'UPDATE member SET nickname = ?, msg = ?, otp_flg = ? 
                WHERE id = ' . $user_id . ' AND company_id = ' . $company_id;
        return $this->conn->writeTransact(
            $sql, [
                $name,
                $msg,
                ( $otp ? 1 : 0 )
            ]
        );
    }

    /**
     * Get all users in list by specified user type code.
     *
     * NOTE: You cannot show the member in status = 0 in the table list.
     *       Because the members are removed by the look of it!
     *
     * @2024-06-27: 取消針對 stage & network 的字串做關鍵字搜尋
     *
     * @param int   $company_id
     * @param int   $except_mem
     * @param int   $pageIndex
     * @param int   $per
     * @param array $orders
     * @param string $search
     * @param array $usr_type
     * @return array|int
     */
    public function getMemberTable(
        int    $company_id,
        int    $except_mem,
        int    $pageIndex,
        int    $per,
        array  $orders = [],
        string $search = '',
        array  $usr_type = []
    ) {
        if ( $company_id <= 0 ) {
            return static::PROC_INVALID;
        } else if ( $except_mem < 0 ) { // 0 = no member to skip
            return static::PROC_INVALID;
        } else if ( $pageIndex < 0 ) {
            return static::PROC_INVALID;
        } else if ( $per < 0 || $per > 200 ) {
            return static::PROC_INVALID;
        }

        // for specified member type
        if ( !empty( $usr_type ) && !ValueValidate::isIntArray( $usr_type ) ) {
            return static::PROC_INVALID;
        }
        $sql_member_type = empty( $usr_type ) ? '' : ( ' AND m.type IN (' . implode(',', $usr_type ) . ') ' );

        // check orders
        $orderbySQL = parent::datatablesOrder2Sql(
            $orders, [
                'm.email',
                'm.type',
                'm.nickname',
                'g.name',
                'm.status'
            ]
        );
        // if nothing for order, set default.
        if ( empty( $orderbySQL ) ) {
            $orderbySQL = 'm.type ASC, g.name ASC';
        }

        // @2024-06-27 取消使用 stage & network
        // for search string.
        $searchCondition = parent::fulltextSearchSQL(
            $search,
            [ 'm.email', 'm.nickname', 'm.msg' ],
            [ 'g.name' ]
        );
        if ( !empty( $searchCondition ) ) {
            $searchCondition = ' AND ' . $searchCondition;
        }

        // for member exception
        $exceptCondition = $except_mem > 0 ? ' AND m.id !=' . $except_mem : '';
        
        $table = static::PROC_FAIL;
        $stmt  = NULL;
        try {
            // count total page
            $stmt = $this->conn->pdo->query(
                'SELECT COUNT( DISTINCT( m.id ) ) 
                 FROM member AS m 
                 INNER JOIN roles AS g ON g.id = m.role_id '.
                 // LEFT JOIN '.$sql_net_tab.' AS net_tab ON net_tab.mem_id = m.id
                 // LEFT JOIN '.$sql_stage_tab.' AS stage_tab ON stage_tab.mem_id = m.id
                'WHERE m.status != 0 AND m.company_id = '.$company_id.
                $sql_member_type.
                $exceptCondition.
                $searchCondition );
            if ( $stmt !== false ) {
                $num = (int)$stmt->fetchColumn();
                // initial output
                $table = parent::datatableProp( $num );
                if ( $num > 0 ) {
                    // NOTE: displayer_situation_mem 當中建立 company_id 欄位，
                    //       才能有效區隔使用者萬一亂轉換公司後，有權限越界的狀況發生
                    $stmt = $this->conn->pdo->query(
                        'SELECT m.id, 
                                m.email, 
                                m.nickname, 
                                m.status, 
                                m.type, 
                                g.name AS role_name
                         FROM member AS m 
                         INNER JOIN roles AS g ON g.id = m.role_id 
                        WHERE m.status != 0 AND m.company_id = ' . $company_id .
                        $sql_member_type .
                        $exceptCondition . 
                        $searchCondition . 
                        ' GROUP BY m.id, m.email, m.nickname, m.status, m.type, g.name 
                          ORDER BY ' . $orderbySQL . 
                        ' LIMIT ' . $pageIndex . ', ' . $per );
                    while ( $row = $stmt->fetch() ) {
                        $table['data'][] = [
                            'id'      => $row['id'],
                            'email'   => $row['email'],
                            'name'    => $row['nickname'],
                            'status'  => $row['status'],
                            'type'    => $row['type'],
                            'role'    => $row['role_name']
                        ];
                    }
                }
            }
        } catch ( Exception $e ) {
            $table = $this->conn->sqlExceptionProc( $e );
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $table;
    }
    
    // ======================= roles ======================= 
    
    /**
     * 
     * @param int $company_id
     * @return bool|array
     */
    private function getCompanyPlan ( int $company_id )
    {
        if ( $company_id <= 0 ) {
            return false;
        }
        
        $plan_role = false;
        $stmt      = NULL;
        try {
            $stmt = $this->conn->pdo->query(
                'SELECT r.*
                 FROM company AS c
                 INNER JOIN company_plan AS p ON p.id = c.plan_id
                 INNER JOIN roles_properties AS r ON r.role_id = p.role_id
                 WHERE c.id = ' . $company_id );
            if ( $stmt !== false && $stmt->rowCount() > 0 ) {
                $plan_role = $stmt->fetch();
                // keep all permissions but header and ending.
                unset( $plan_role['role_id'] );
                unset( $plan_role['modify_on'] );
            }
        }  catch ( Exception $e ) {}
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
            $stmt = NULL;
        }
        return $plan_role;
    }

    /**
     * show all group name in different permission.
     *
     * @param int $company_id
     * @param int $pageIndex
     * @param int $per
     * @param array $orders
     * @param string $search
     * @return array|int
     */
    public function getRolesTab ( 
        int $company_id, 
        int $pageIndex, 
        int $per, 
        array $orders = [], 
        string $search = ''
    ) {
        if ( $company_id <= 0 ) { // if company is 0, it means the role is default value for each plan.
            return static::PROC_INVALID;
        } else if ( $pageIndex < 0 || $per < 0 || $per > 200 ) {
            return static::PROC_INVALID;
        }
        // check orders
        $orderbySQL = parent::datatablesOrder2Sql( 
            $orders, 
            [ 'id', 'name' ] );
        // if nothing for order, set default.
        if ( empty( $orderbySQL ) ) {
            $orderbySQL = 'id ASC';
        }
        // for search string.
        $searchCondition = parent::fulltextSearchSQL(
            $search,
            [ 'name' ] );
        if( !empty( $searchCondition ) ) {
            $searchCondition = ' AND ' . $searchCondition;
        }
        
        $table = static::PROC_FAIL;
        $stmt  = NULL;
        try {
            // IMPORTANT: count total page, and DON'T let company_id = 0 be in
            $stmt = $this->conn->pdo->query(
                'SELECT COUNT( id ) FROM roles WHERE company_id =' . $company_id . $searchCondition );
            if ( $stmt !== false ) {
                $num = (int)$stmt->fetchColumn();
                // initial output
                $table = parent::datatableProp( $num );
                if ( $num > 0 ) {
                    $stmt = $this->conn->pdo->query(
                        'SELECT id, name
                         FROM roles
                         WHERE company_id =' . $company_id . $searchCondition .
                        ' ORDER BY ' . $orderbySQL .
                        ' LIMIT ' . $pageIndex . ', ' . $per );
                    while ( $row = $stmt->fetch() ) {
                        $table['data'][] = [
                            'id' => $row['id'],
                            'name' => $row['name'],
                            'editable' => 1 // ( $row['company_id'] > 0 ? 1 : 0 )
                        ];
                    }
                }
            }
        } catch ( Exception $e ) {
            $table = static::PROC_SERV_ERROR;
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
            $stmt = NULL;
        }
        return $table;
    }
    
    /**
     * Create a new role of company (or recycle a used one, prevent waste id)
     * 
     * IMPORTANT: The role cannot over power of super admin and over power of assigned company plan.
     * 
     * @param int $company_id
     * @param string $role_name
     * @param array $perms
     * @return int
     */
    public function newRole ( int $company_id, string $role_name, array $perms ): int
    {
        $role_name = trim( $role_name );
        if ( empty( $role_name ) || ValueValidate::safeStrlen( $role_name ) > 45 ) {
            return static::PROC_INVALID;
        } else if ( $company_id <= 0 ) { // 0 ids are system default values for every client side to use, no client can change them
            return static::PROC_INVALID;
        }
        // step 1: take out the company plan default role and compare to the role you want to create because none can be over power to it.
        // get the plan default value of role.
        $plan_role = self::getCompanyPlan( $company_id );
        // IMPORTANT: check keys(permission names) are the same to company plan and
        //            all values of customized roles are not over the role limit of plan of each column.
        if ( empty( $plan_role ) ) {
            return static::PROC_SERV_ERROR;
        } else if ( !ValueValidate::isArrayKeysEqual( $plan_role, $perms ) ) {
            return static::PROC_INVALID;
        }
        // step 2. comparing all role properties and ensure all permission values are not over company plan.
        foreach ( $perms as $k => $v ) {
            // not allow value except 0,1,2,3 => none,read,write,delete.
            if ( !is_int( $v ) || $v > $plan_role[ $k ] || $v < 0 ) { 
                return static::PROC_INVALID;
            }
        }
        
        // sql for find out gap of auto increment in table
        // CONCAT( z.expected, IF( z.got - 1 > z.expected, CONCAT( \' thru \',z.got-1), \'\' ) ) AS missing
        $sqlIdGap = 'SELECT z.expected
                     FROM (
                        SELECT
                            @rownum := @rownum + 1 AS expected,
                            IF( @rownum = id, 0, @rownum := id ) AS got
                        FROM
                            ( SELECT @rownum := 0 ) AS a JOIN roles ORDER BY id
                    ) AS z WHERE z.got != 0 LIMIT 1';
        $result = static::PROC_FAIL;
        $stmt   = NULL;
        try {
            $this->conn->pdo->beginTransaction();
            // if there is a gap, use the missing id to insert to,
            // or system will execute auto increment when the gap sql return NULL.
            $stmt = $this->conn->pdo->prepare(
                'INSERT INTO roles ( id, company_id, name ) 
                 SELECT (' . $sqlIdGap . '), ' . $company_id .', ? 
                 FROM DUAL WHERE NOT EXISTS ( SELECT id FROM roles WHERE company_id = ' .
                $company_id. '  AND name = ? )' );
            if ( $stmt->execute( array( $role_name, $role_name ) ) ) {
                if ( $stmt->rowCount() > 0 ) {
                    $new_id = $this->conn->pdo->lastInsertId();
                    
                    $permsKeys = array_keys( $perms );
                    $permsVal  = array_values( $perms );
                    $sqlDupValues = '';
                    foreach ( $permsKeys as $v ) {
                        $sqlDupValues .= '`' . $v . '` = VALUES(`' . $v . '`),';
                    }
                    $sqlDupValues = rtrim( $sqlDupValues, ',' ); // remove the comma at the end
                    $stmt = $this->conn->pdo->prepare(
                        'INSERT INTO roles_properties (`role_id`,`'.implode('`,`', $permsKeys) . 
                        '`) VALUES (' . $new_id . ',' . parent::pdoPlaceHolders( '?', count( $permsKeys ) ) .
                        ') ON DUPLICATE KEY UPDATE ' . $sqlDupValues );
                    if ( $stmt->execute( $permsVal ) && $stmt->rowCount() > 0 ) {
                        $this->conn->pdo->commit();
                        $result = self::PROC_OK;
                    } else {
                        $this->conn->pdo->rollBack();
                    }
                } else {
                    $this->conn->pdo->rollBack();
                    $result = static::PROC_DUPLICATE;
                }
            } else {
                $this->conn->pdo->rollBack();
            }
        } catch ( Exception $e ) {
            $result = $this->conn->sqlExceptionProc($e);
        }
        if ( $stmt !== NULL ) {
            $stmt->closeCursor();
        }
        return $result;
    }

    /**
     * Remove a group.
     * NOTE: if there is anyone using the role, you cannot disable it.
     *       Because there is a foreign-key from member table
     * NOTE: If a role is removed in roles table, the role property will be clean
     *       in role_properties table automatically because of a foreign-key to roles table.
     *
     * @param int $company_id
     * @param int $role_id
     * @return int
     */
    public function removeRole ( int $company_id, int $role_id ): int
    {
        // 0 ids are system default values for every client side to use, no client can change them
        if ( $company_id <= 0 ) { 
            return static::PROC_INVALID;
        } else if ( $role_id <= 0 ) {
            return static::PROC_INVALID;
        }
        return $this->conn->writeTransact( 'DELETE FROM roles WHERE id = ' . $role_id . ' AND company_id = ' . $company_id );
    }
    
    /**
     * no need to show up the super administrator access list.
     *
     * @param int $company_id
     * @param int $role_id
     * @return array|int
     */
    public function rolePermissions ( int $company_id, int $role_id )
    {
        if ( $role_id <= 0 ) {
            return static::PROC_INVALID;
        } else if ( $company_id <= 0 ) {
            return static::PROC_INVALID;
        }
        
        // IMPORTANT: you have to ensure role_name is not duplicate to any other column name in table roles_properties.
        // NOTE: the company owner's role is always in company_id = 0 because it is a default for plan role.
        $perms = $this->conn->selectTransact(
            'SELECT r.company_id, r.name AS role_name, p.* 
             FROM roles AS r 
             INNER JOIN roles_properties AS p ON p.role_id = r.id 
             WHERE r.id = ' . $role_id . ' AND r.company_id IN(0, ' . $company_id . ')' );
        if ( is_int( $perms ) ) {
            return $perms;
        }
        $out = static::PROC_FAIL;
        if( !empty( $perms ) ) {
            $perms = $perms[0];
            $out = [
                'name'     => $perms['role_name'],
                'editable' => ( $perms['company_id'] > 0 ? 1 : 0 )
            ];
            // extract role properties' column.
            unset( $perms['company_id'] );
            unset( $perms['role_name'] );
            unset( $perms['role_id'] );
            unset( $perms['modify_on'] );
            $out['perms'] = $perms;
        }
        return $out;
    }
    
    /**
     * Edit member role access row
     *
     * @param int $company_id it must be over 0.
     * @param int $role_id role id
     * @param array $perms With database columns and values.
     * @return int
     */
    public function editRolePerms ( int $company_id, int $role_id, array $perms ): int
    {
        // 0 ids are system default values for every client side to use, no client can change them
        // so $company_id must > 0.
        if( $company_id <= 0 || $role_id <= 0 || empty( $perms )  ) {
            return static::PROC_INVALID;
        }
        // step 1: take out the company plan default role and compare to the role you want to create because none can be over power to it.
        // get the plan default value of role.
        $plan_role = self::getCompanyPlan( $company_id );
        // IMPORTANT: check keys(permission names) are the same to company plan and
        //            all values of customized roles are not over the role limit of plan of each column.
        if ( empty( $plan_role ) ) {
            return static::PROC_SERV_ERROR;
        } else if ( !ValueValidate::isArrayKeysEqual( $plan_role, $perms ) ) {
            return static::PROC_INVALID;
        }
        // step 2. comparing all role properties and ensure all permission values are not over company plan.
        $allowedFields = array_keys($plan_role);
        $sqlAccessUpdateArr = [];
        foreach ($perms as $k => $v) {
            // not allow value except 0,1,2,3 => none,read,write,delete.
            if (!in_array($k, $allowedFields, true) || !is_int($v) || $v > $plan_role[$k] || $v < 0) {
                return static::PROC_INVALID;
            }
            $sqlAccessUpdateArr[] = "`$k` = $v";
        }
        $sqlAccessUpdate = implode(',', $sqlAccessUpdateArr);

        $out  = static::PROC_FAIL;
        try {
            $this->conn->pdo->beginTransaction();
            $is_done = $this->conn->pdo->exec(
                'UPDATE roles_properties AS p
                 INNER JOIN roles AS r ON r.id = p.role_id
                 SET ' . $sqlAccessUpdate .
                ' WHERE p.role_id = ' . $role_id . ' AND r.company_id = ' . $company_id
            );
            if ($is_done === false) {
                $this->conn->pdo->rollBack();
                return $out;
            }
            
            // remove all member access/api token in relationship.
            if ($is_done > 0) {
                $accessCleared = $this->conn->pdo->exec('DELETE FROM oauth_access WHERE user_id IN(SELECT id FROM member WHERE role_id = ' . $role_id . ')');
                $apiCleared = $accessCleared !== false
                    ? $this->conn->pdo->exec('DELETE FROM oauth_api WHERE user_id IN(SELECT id FROM member WHERE role_id = ' . $role_id . ')')
                    : false;
                if ($accessCleared !== false && $apiCleared !== false) {
                    $this->conn->pdo->commit();
                    $out = static::PROC_OK;
                } else {
                    $this->conn->pdo->rollBack();
                }
            } else {
                $this->conn->pdo->commit();
                $out = static::PROC_OK;
            }
        } catch ( Exception $e ) {
            $out = $this->conn->sqlExceptionProc( $e );
        }
        return $out;
    }
}