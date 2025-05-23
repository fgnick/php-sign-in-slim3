<?php
namespace Gn;

use ErrorException;
use Slim\Views\PhpRenderer;

/**
 * PHPMailer in Linux command line execution:
 * User this class, you can send mail in the OS background thread.
 * 
 * Notice: It is not for normal MVC controller in Slim framework.
 *
 * @author Nick Feng
 * @since 1.0
 */
class MailerCtl
{
    const BATCH_LIMIT = 100;
    
    /**
     * Settings of mailer from Slim 3 global settings.
     * 
     * @var array
     */
    protected $settings = [
        'os_script_url' => 'where the phpmailer for OS execution',  // for Linux execute
        'log_url'       => 'where is the path for the log saving',  // for Linux execute
        'active'        => false,                     // turn off the flag, system will not send email anyway.
        'debug'         => 0,                         // Enable verbose debug output
        'is_smtp'       => true,                      // Set mailer to use SMTP
        'host'          => 'smtp.example.com',        // Specify main and backup SMTP servers
        'port'          => 587,                       // the default TCP port to connect to
        'smtp_auth'     => true,                      // Enable SMTP authentication
        'user_name'     => 'example@example.com',     // SMTP username
        'user_pw'       => 'password',                // SMTP password
        'smtp_secure'   => 'tls',                     // Enable TLS encryption, `ssl` also accepted
        'is_html'       => true,
        'templates'     => 'where the html template render for'
    ];
    
    /**
     * escapeshellarg for Linux exec
     *
     * @var array
     */
    protected $m_settings = NULL;

    /**
     *
     * @var object
     */
    protected $render = NULL;

    /**
     * Constructor
     *
     * @param mixed $settings
     * @throws ErrorException
     */
    public function __construct( $settings )
    {
        $this->render = new PhpRenderer( $settings['templates'] );
        self::setMailerSettings( $settings );
    }
    
    /**
     * Destructor: remove all settings
     */
    public function __destruct() 
    {
        unset( $this->m_settings );
    }

    /**
     * Initialize mailer settings.
     *
     * @param array $mailerSettings
     * @throws ErrorException
     */
    public function setMailerSettings( array $mailerSettings )
    {
        // make sure all keys are the same.
        $diff_arr = array_diff_key( $this->settings, $mailerSettings );
        if ( empty( $diff_arr ) ) {
            $this->settings = $mailerSettings;
            $this->m_settings = escapeshellarg( json_encode( [
                'debug'       => $this->settings['debug'],         // Enable verbose debug output
                'host'        => $this->settings['host'],          // Specify main and backup SMTP servers
                'smtp_auth'   => $this->settings['smtp_auth'],     // Enable SMTP authentication
                'user_name'   => $this->settings['user_name'],     // SMTP username
                'user_pw'     => $this->settings['user_pw'],       // SMTP password
                'smtp_secure' => $this->settings['smtp_secure'],   // Enable TLS encryption, `ssl` also accepted
                'port'        => $this->settings['port'],          // TCP port to connect to
                'is_html'     => $this->settings['is_html'],
                'is_smtp'     => $this->settings['is_smtp']
            ] ) );
        } else {
            throw new ErrorException('phpmailer settings error');
        }
    }
    
    /**
     * Default email sender. To execute Linux command to call php file.
     *
     * @param array $to
     * @param string $subject
     * @param string $view_path for Slim 3 php renderer
     * @param array $data
     * @return bool
     */
    public function sendMail(array $to, string $subject, string $view_path, array $data=[] ): bool
    {
        $isSent = false;
        if ( $this->settings['active'] === false ) {
            return false;
        }

        $value = [];
        foreach ( $to as $email ) {
            $value[$email] = [
                'subject' => $subject,
                'body'    => $this->render->fetch( $view_path, $data )
            ];
            
            if ( count( $value ) >= self::BATCH_LIMIT ) {
                $isSent = self::executeMailerInOs( $value );
                $value = []; // clean array.
                if ( $isSent !== false ) {
                    sleep(1);
                } else {
                    break; // end the loop.
                }
            }
        }
        if( !empty( $value ) ) {
            $isSent = self::executeMailerInOs( $value );
        }
        unset( $value );
        return $isSent;
    }
    
    /**
     * After freshman get access permission, you can use the function to send an email.
     *
     * @param array $addrs
     * @param string $subject_txt
     * @return bool
     * @deprecated
     */
    public function AccountAccess ( array $addrs, string $subject_txt = 'Account Access ' ): bool
    {
        $isSent = false;
        if ( empty( $addrs ) ) {
            return false;
        }

        $value = [];
        foreach ( $addrs as $email => $v ) {
            if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false ) {
                if ( $v === 'ok' ) {
                    $subject_txt .= 'Success';
                } else {
                    $subject_txt .= 'Fail';
                }
                
                // collect values for sending in batch.
                $value[ $email ] = [
                    'subject' => $subject_txt,
                    'body'    => $this->render->fetch( 'new-access.phtml',
                         [
                             'user_account' => $email,
                             'status'       => $v === 'ok'
                         ])
                ];
                
                // send out if batch to the limit number.
                if( count( $value ) >= self::BATCH_LIMIT ) {
                    $isSent = self::executeMailerInOs( $value );
                    $value = []; // clean array.
                    if ( $isSent !== false ) {
                        sleep(1);
                    } else {
                        break; // end the loop.
                    }
                }
            }
        }
        if( !empty( $value ) ) {
            $isSent = self::executeMailerInOs( $value );
        }
        unset( $value );
        return $isSent;
    }
    
    /**
     * Use Linux to execute the php file in the background.
     * data must like this:
     *  [
     *      'subject' => $subject,
     *      'body'    => $this->render->fetch( $view_path, $data ),
     *      ...
     *  ]
     * @param array $data If setting is inactive, email will not send out, and return false.
     * @return bool
     */
    protected function executeMailerInOs( array $data ): bool
    {
        if ( $this->settings['active'] === false ) {
            return false;
        }
        
        $out = escapeshellarg( json_encode( $data ) );
        if ( file_exists( $this->settings['os_script_url'] ) ) {
            if ( isset( $this->settings['log_url']) && !empty( $this->settings['log_url'] ) ) {
                system( 'nohup php ' . $this->settings['os_script_url'] . ' ' . $this->m_settings . ' ' . $out . ' >> ' . $this->settings['log_url'] . ' 2>&1 &' );
            } else {
                system( 'nohup php ' . $this->settings['os_script_url'] . ' ' . $this->m_settings . ' ' . $out . ' > /dev/null 2>&1 &' );
            }
            return true;
        }
        return false;
    }
}