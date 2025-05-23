<?php
namespace Gn;

use ErrorException;

use Gn\Ctl\ApiBasicCtl;
use Gn\Fun\RegisterFun;
use Gn\Interfaces\MailerInterface;

// from Slim
use Firebase\JWT\JWT;
use Gn\Lib\ValueValidate;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * member controller of system
 *
 * @author Nick Feng
 * @since 1.0
 */
class MemberCtrl extends ApiBasicCtl implements MailerInterface
{
    /**
     * member management class
     * @var RegisterFun
     */
    protected $register;

    /**
     * settings of Slim framework.
     * 
     * @var array
     */
    protected $settings;

    /**
     * Constructor.
     *
     * @param Container $container
     * @throws ErrorException
     */
    public function __construct( Container $container )
    {
        parent::__construct( $container );
        $this->register = new RegisterFun ( $container );
        $this->settings = $container->get('settings');
    }
    
    /**
     * Invite a member via system. B2B By this way to create a new member.
     * 
     * @author Nick
     * @param array $payload
     * @param string $email
     * @return bool If success, return URL string; Otherwise, false in boolean.
     */
    private function mailMemberInvitation ( array $payload, string $email ): bool
    {
        if ( empty( $email ) || empty( $payload ) ) {
            return false;
        }
        // generate an url for user confirming.
        $token = rawurlencode( 
            JWT::encode( 
                $payload, 
                $this->settings['oauth']['pw_reset_secret'], 
                $this->settings['oauth']['algorithm'] 
            )
        );
        $resetURL = $this->settings['app']['dns']['self'] . $this->settings['app']['url']['reset_pw'] . $token;
        // send mail to member.
        $mailer = $this->container->get('mailer');
        $is_sent = $mailer->sendMail( 
            [ $email ], 
            static::MAIL_TITLE_INVITATION, 
            static::MAIL_HTMLBODY_INVITATION, 
            [ 'url' => $resetURL ] 
        );
        if ( !$is_sent ) {
            // NOTE: Just record reset URL into log for testing. If released, PLEASE REMOVE it in log recording.
            $this->container->logger->emergency( $email . ' invitation mail error: ' . $resetURL );
            return false;
        }
        return true;
    }
    
    /**
     * Inviting a new member.
     * 
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function post_InviteMember ( Request $request, Response $response, array $args ): Response
    {
        $email = $request->getParsedBodyParam( 'email' );
        $type  = $request->getParsedBodyParam( 'type' );
        $role  = $request->getParsedBodyParam( 'role' );
        if ( is_string( $email ) && !empty( $email ) 
            && is_int( $type ) && $type > 0 
            && is_int( $role ) && $role > 0 ) 
        {
            // you cannot invite a member level is higher than/equal to the inviter.
            if ( $type > $this->container['usr']['type'] &&
                $this->container['usr']['permission']['member'] >= 2 
            ) {
                // IMPORTANT: check the role id is legal and from the same company.
                //            database must have the permissions of role, if not, it means data error!!
                $perms = $this->register->getRolePerms( $this->container['usr']['company'], $role ); 
                if ( is_array($perms) ) {
                    // IMPORTANT: role的創建不能高於 super admin( company/group owner ), 也不能高過本次賦予權限的編輯者
                    //            暫時這邊不檢查，因為可能只是代理邀請。但是實質創造 role 的人並不是他人。
                    $data = $this->register->InitMember( $email, $this->container['usr']['company'], $type, $role );
                    if ( is_array( $data ) ) {
                        self::resp_decoder( static::PROC_OK, '', [ 'id' => $data['mem_id'] ] );

                        // set a log
                        $this->container->logger->notice( $email . ' invited by member ' . $this->container['usr']['id'] );

                        // send mail to member.
                        self::mailMemberInvitation( $data['payload'], $email );
                    } else {
                        self::resp_decoder( $data );

                        // set a log
                        $this->container->logger->warn(
                            $email . ' invited error by member(' . $this->container['usr']['id'] . '): ' . $this->respJson['data'] );
                    }
                } else {
                    self::resp_decoder($perms);
                    // set a log
                    $this->container->logger->emergency( 'Member invitation database role permissions finding error!' );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }
    
    /**
     * Invite an existed member again.
     *
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function post_ReinviteMember ( Request $request, Response $response, array $args ): Response
    {
        $email = $request->getParsedBodyParam( 'email' );
        if ( is_string( $email ) && !empty( $email ) ) {
            if ( $this->container['usr']['permission']['member'] >= 2 ) {
                // you cannot to re-modify the original invitation, the only one you can do just reset password and refresh token.
                $payload = $this->register->resetPassword( $email, 3 );
                if ( is_array( $payload ) ) {
                    self::resp_decoder( static::PROC_OK );

                    // set a log
                    $this->container->logger->notice( $email . ' has re-invited by member ' . $this->container['usr']['id'] );

                    // send mail to member.
                    self::mailMemberInvitation( $payload, $email );


                    // TODO: 也可以使用這種方式，可以用集中比對 session ID，並且將發送出去的 ID 用另外的 table 去記錄下來，也可以用 redis 的方式去記憶。取決於個人需求。
                    // 用 token 的方式唯一的缺點就是，你拿這個 token 只要沒有過期，都可以進入到這一頁，但是這個 token 所被記憶下來，是否可以進行密碼初始化的部位，如果
                    // 已經使用過了，則 table 之中的紀錄也會消逝。因此，即便是可以進到這一頁，但是依舊無法重複性的使用他去進行密碼的初始化。

                } else {
                    self::resp_decoder( $payload );

                    // set a log
                    $this->container->logger->warn( 
                        $email . ' re-invited fail by member(' . $this->container['usr']['id'] . '): ' . $this->respJson['data'] );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }

    /**
     * Edit SELF profile( not others' profile ).
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @author Nick
     */
    public function get_AccountProfile (Request $request, Response $response, array $args): Response
    {
        $data = $this->register->getMemberData( 
            $this->container['usr']['company'], 
            $this->container['usr']['id'], 
            true 
        );
        if ( is_array( $data ) ) {
            self::resp_decoder( static::PROC_OK, '', $data );
        } else {
            self::resp_decoder( static::PROC_FAIL );
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }

    /**
     * Change self information of profile by self.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ErrorException
     * @author Nick
     */
    public function post_EditAccountProfile (Request $request, Response $response, array $args): Response
    {
        $name = $request->getParsedBodyParam( 'name' );
        $msg  = $request->getParsedBodyParam( 'msg' );
        $otp  = $request->getParsedBodyParam( 'otp' );
        if ( is_string( $name ) && is_string( $msg ) && is_bool( $otp ) ) {
            $out = $this->register->editMemberProfile( 
                $this->container['usr']['company'],
                $this->container['usr']['id'], 
                $name, 
                $msg, 
                $otp 
            );
            self::resp_decoder( $out );

            // set a log
            if ( $this->respJson['status'] === 1 ) {
                $this->container->logger->notice( 'account profile changed: member ' . $this->container['usr']['id'] );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }
    
    /**
     * Change the self password.
     *
     * @author Nick
     * @method POST
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function post_ChangePassword (Request $request, Response $response, array $args): Response
    {
        $pw    = $request->getParsedBodyParam( 'pw' );
        $ex_pw = $request->getParsedBodyParam( 'ex_pw' );
        if ( is_string( $pw ) && is_string( $ex_pw ) && !empty( $pw ) && !empty( $ex_pw ) ) {
            $out = $this->register->modifyPw( $this->container['usr']['id'], $pw, $ex_pw );
            if ( is_array( $out ) ) {
                self::resp_decoder( static::PROC_OK );  // complete in perfect
                // set a log
                $this->container->logger->notice( $out['email'] . ' password has been changed' );
                // send mail to member for confirming.
                $mailer = $this->container->get('mailer');
                $sent_mail = $mailer->sendMail(
                    array( $out['email'] ),
                    static::MAIL_TITLE_PW_CHANGED,
                    static::MAIL_HTMLBODY_PW_CHANGED 
                );
                if ( !$sent_mail ) {
                    // set a log
                    $this->container->logger->emergency( $out['email'] . ' password-changed mail error.' );
                }
            } else {
                self::resp_decoder( static::PROC_FAIL );
                // set a log
                $this->container->logger->emergency( 'member password change fail(member #' . $this->container['usr']['id'] . ')' );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }

    /**
     * Create a new member role ( it will check name duplication ).
     *
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function post_CreateRole (Request $request, Response $response, array $args): Response
    {
        $name  = $request->getParsedBodyParam( 'name' );
        $perms = $request->getParsedBodyParam( 'perms' );
        if ( is_string( $name ) && is_array( $perms ) && !empty( $name ) && !empty( $perms ) ) {
            if ( $this->container['usr']['permission']['role'] >= 2 ) {
                // IMPORTANT: check keys(permission names) are equal to legal role properties' columns 
                if ( ValueValidate::isArrayKeysEqual( $this->container['usr']['permission'], $perms ) ) {
                    // IMPORTANT: role的創建不能高於super admin( company/group owner ), 也不能高過本次賦予權限的編輯者
                    foreach ( $perms as $k => $v ) {
                        if ( !is_int( $v ) || $v > $this->container['usr']['permission'][ $k ] || $v < 0 ) {
                            self::resp_decoder( static::PROC_NO_ACCESS );
                            return $response->withJson( $this->respJson, $this->respCode );
                        }
                    }
                    
                    $code = $this->register->createRole( $this->container['usr']['company'], $name, $perms );
                    self::resp_decoder( $code );
                    // set a log
                    if ( $this->respJson['status'] === 1 ) {
                        $this->container->logger->notice( 
                            'member ' . $this->container['usr']['id'] . ' create a new role: ' . $name . ', ' .
                            parent::jsonLogStr( $perms ) );
                    }
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }

    /**
     * Remove and recycle role id.
     *
     * NOTE: If there is any role in using, you cannot remove it.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ErrorException
     * @author Nick
     */
    public function del_RemoveRole ( Request $request, Response $response, array $args ): Response
    {
        $role_id = $request->getParsedBodyParam( 'id' );
        if ( is_int( $role_id ) && $role_id > 0 ) {
            if ( $this->container['usr']['permission']['role'] >= 3 ) {
                $code = $this->register->recycleRole( $this->container['usr']['company'], $role_id );
                self::resp_decoder( $code );
                // set a log
                if ( $this->respJson['status'] === 1 ) {
                    $this->container->logger->notice( 'member ' . $this->container['usr']['id'] . ' recycles a role ' . $role_id );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }
    
    /**
     * Get member role list.
     *
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function post_RoleList (Request $request, Response $response, array $args): Response
    {
        $vars = $request->getParsedBody();
        if ( parent::is_dataTablesParams( $vars ) ) {
            if ( $this->container['usr']['permission']['role'] >= 1 || 
                 $this->container['usr']['permission']['member'] >= 2
            ) {
                $table = $this->register->getRoleList(
                    $this->container['usr']['company'],
                    (int)$vars['page'],
                    (int)$vars['per'],
                    $vars['order'],
                    $vars['search']
                );
                if ( is_array( $table ) ) {
                    parent::resp_datatable( $vars['draw'], $table['total'], $table['data'] );
                } else {
                    self::resp_decoder( $table );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }
    
    /**
     * edit a type of role permission.
     *
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function post_RolePermission (Request $request, Response $response, array $args): Response
    {
        $role_id = $request->getParsedBodyParam( 'id' );
        $perms   = $request->getParsedBodyParam( 'perms' );
        if ( is_int( $role_id ) && $role_id > 0 && is_array( $perms ) && !empty( $perms ) ) {
            // 請檢查 role_id 是否與 requester 使用的是同一個？如果是，則不可以給它修改！
            $requester_data = $this->register->getMemberData( 
                $this->container['usr']['company'], 
                $this->container['usr']['id'] 
            );

            if ( $this->container['usr']['permission']['role'] >= 2 && $role_id != $requester_data['role_id'] ) {
                // IMPORTANT: check keys(permission names) are equal to legal role properties' columns
                if ( ValueValidate::isArrayKeysEqual( $this->container['usr']['permission'], $perms ) ) {
                    // IMPORTANT: role 的創建不能高於 super admin ( company/group owner ), 也不能高過本次賦予權限的編輯者
                    foreach ( $perms as $k => $v ) {
                        if ( !is_int( $v ) || $v > $this->container['usr']['permission'][ $k ] || $v < 0 ) {
                            self::resp_decoder( static::PROC_NO_ACCESS );
                            return $response->withJson( $this->respJson, $this->respCode );
                        }
                    }

                    // you can do next steps if all perms are legal.
                    $code = $this->register->setRolePerms( 
                        $this->container['usr']['company'], 
                        $role_id, $perms 
                    );
                    self::resp_decoder( $code );

                    // set a log
                    if ( $this->respJson['status'] === 1 ) {
                        $this->container->logger->notice(
                            'member ' . $this->container['usr']['id'] . ' edit the role(' . 
                            $role_id . ') permission: ' . parent::jsonLogStr( $perms ) );
                    }
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }
    
    /**
     * View a type of role permission.
     * 
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function get_RolePermission ( Request $request, Response $response, array $args ): Response
    {
        $role_id = (int)$request->getQueryParam( 'id', '0' );
        if ( $role_id > 0 ) {
            if ( $this->container['usr']['permission']['role'] >= 1 ) {
                $data = $this->register->getRolePerms( $this->container['usr']['company'], $role_id );
                if ( is_array( $data ) ) {
                    self::resp_decoder( static::PROC_OK, '', $data );
                } else {
                    self::resp_decoder( static::PROC_FAIL );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }
    
    /**
     * Get member list of company, member, or guest
     * 
     * NOTE: Because ISSUE needs to use member list for any one,
     *       Change to be anyone can see it without any permission by FGN at 4th Des. 2020.
     *
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function get_GetMemberList (Request $request, Response $response, array $args): Response
    {
        $vars        = $request->getQueryParams();
        $usr_type    = $request->getQueryParam( 'usr_type', [] );
        if ( parent::is_dataTablesParams( $vars )
            && is_array( $usr_type )
        ) {
            if ( $this->container['usr']['permission']['member'] >= 1 ) {
                // NOTE: FGN take the exception person off at 4th Des. 2020,
                //       so you can see yourself in the list.
                $table = $this->register->getMemberList(
                    $this->container['usr']['company'],
                    0,
                    (int)$vars['page'],
                    (int)$vars['per'],
                    $vars['order'],
                    $vars['search'],
                    $usr_type
                );
                
                if ( is_array( $table ) ) {
                    parent::resp_datatable( $vars['draw'], $table['total'], $table['data'] );
                } else {
                    self::resp_decoder( $table );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }

    /**
     * Get member data for management(even self information)
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @author Nick
     */
    public function get_GetMemberData (Request $request, Response $response, array $args): Response
    {
        $mem_id = (int)$args['id'];
        if ( $mem_id > 0 ) {
            $is_access = 0;
            if ( $this->container['usr']['id'] === $mem_id ) { // get the member information for requester self.
                $is_access = 1;
            } else if ( $this->container['usr']['permission']['member'] >= 1 ) { // looking for other member account information.
                $is_access = 2;
            }
            if ( $is_access > 0 ) {
                $data = $this->register->getMemberData( $this->container['usr']['company'], $mem_id, true );
                if ( is_array( $data ) ) {
                    switch ( $is_access ) {
                        case 1:
                            self::resp_decoder( static::PROC_OK, '', $data );
                            break;
                        case 2:
                            self::resp_decoder( static::PROC_OK, '', $data );
                            break;
                        default:
                            self::resp_decoder( static::PROC_FAIL );
                    }
                } else {
                    self::resp_decoder( static::PROC_FAIL );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }

    /**
     * For manager to edit member status, role, type (not self).
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ErrorException
     * @author Nick
     * @method POST
     */
    public function post_EditMember (Request $request, Response $response, array $args): Response
    {
        $mem_id = $request->getParsedBodyParam( 'id' );
        $type   = $request->getParsedBodyParam( 'type', 0 );    // 0 means un-modify it
        $role   = $request->getParsedBodyParam( 'role', 0 );    // 0 means un-modify it
        $status = $request->getParsedBodyParam( 'status', 0 );  // 0 means un-modify it
        if ( is_int( $mem_id ) && $mem_id > 0
            && is_int( $type ) && $type >= 0 
            && is_int( $role ) && $role >= 0 
            && is_int( $status ) && $status >= 0 ) 
        {
            // Check api permission
            if ( $this->container['usr']['permission']['member'] >= 2 && 
                 ( $type === 0 || $type > $this->container['usr']['type'] ) 
            ) {
                // IMPORTANT: you cannot modify a person has any permission higher than you!
                if ( $role > 0 ) {
                    $perms = $this->register->getRolePerms( $this->container['usr']['company'], $role ); // another legal permission form
                    // IMPORTANT: role 的創建不能高於 super admin( company/group owner ), 也不能高過本次賦予權限的編輯者
                    if ( is_array( $perms ) ) {
                        // NOTE: because the both permission array are from system returning, so no need to check array keys are equal or not.
                        foreach( $perms['perms'] as $k => $v ) {
                            if ( !is_int( $v ) || $v > $this->container['usr']['permission'][ $k ] || $v < 0 ) {
                                self::resp_decoder( static::PROC_NO_ACCESS );
                                return $response->withJson( $this->respJson, $this->respCode );
                            }
                        }
                    } else {
                        self::resp_decoder( static::PROC_SERV_ERROR );
                        return $response->withJson( $this->respJson, $this->respCode );
                    }
                }
                // if it is legal, you can go to the next.
                $data = $this->register->getMemberData( $this->container['usr']['company'], $mem_id );
                // 1. Can not change the member type higher than requester.
                // 2. Can not allow requester to change member type to be equal/higher with self
                // 3. Can not change member status to 1|2 from 3 (initializing).
                if ( is_array( $data ) && $data['type'] > $this->container['usr']['type'] ) {
                    // 如果這個要被修改的 member 是正在被邀請的人員，則不可以修改他的 status。如果有值送來，也直接改成忽略它
                    if ( $data['status'] === 3 ) {  
                        $status = 0;
                    }
                    $out = $this->register->editMemberState(
                        $this->container['usr']['company'], 
                        $mem_id, 
                        $type, 
                        $role, 
                        $status 
                    );
                    self::resp_decoder( $out );
                    // set a log
                    if ( $out === static::PROC_OK ) {
                        $this->container->logger->notice( 
                            'member(' . $mem_id . ') state changed by member(' . $this->container['usr']['id'] . 
                            '): type=' . $type . ', role=' . $role . ', status=' . $status );
                    }
                } else {
                    self::resp_decoder( static::PROC_NO_ACCESS );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }
}