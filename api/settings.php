<?php
/**
 * Application setting of Slim 3
 *
 * @author Nick Feng
 * @since 1.0
 */
return [
    'settings' => [
        'displayErrorDetails'    => true,     // set to false in production
        'addContentLengthHeader' => false,    // Allow the web server to send the content-length header
        'debug' => true,                      // debug mode
        'mode'  => 'development',             // for developer in debug mode
        
        // Monolog settings
        'logger' => [
            'name'  => 'api',
            'path'  => isset( $_ENV['docker'] ) ? 'php://stdout' : __DIR__ . '/../logs/api.' . date( 'YmdH' ) . '.log',
            'level' => Monolog\Logger::DEBUG,
        ],
        
        'app' => [
            'dns' => [
                'self' => 'http://127.0.0.1'
            ],
            'url' => [
                'login'    => '/login',
                'reset_pw' => '/password/new?v=',
            ],
            'asset' => [
                'json' => __DIR__ . '/../asset/json'
            ],
            'upload' => [
                'ds_service' => __DIR__ . '/../uploads/ds_service'
            ]
        ],

        // for JWT and cookies
        'oauth' => [
            'issuer'             => 'YourIssuerName', // Issuer of the token, you can set it to your domain name or company name.
            'algorithm'           => 'HS256',
            'header'              => 'X-Token',
            'environment'         => 'HTTP_X_TOKEN',
            'access_token_cookie' => '_ac',     // you can remodify the cookie name
            'cookie_secur'        => require __DIR__ . '/../security/ssl/ssl_secur.php',// default is false,
            'access_secret'       => require __DIR__ . '/../security/jwt/secret-access.php',
            'api_secret'          => require __DIR__ . '/../security/jwt/secret-api.php',
            'register_secret'     => require __DIR__ . '/../security/jwt/secret-register.php',
            'pw_reset_secret'     => require __DIR__ . '/../security/jwt/secret-pw-reset.php',
            'internal_access'     => require __DIR__ . '/../security/internal-api/internal-access.php'
        ],
    
        // default design works on Google Workspace Email, so all about the parameters are nessary to Gmail.
        'mailer' => [
            'active'        => false,                     // turn off the flag, system will not send email anyway.
            'os_script_url' => __DIR__ . '/../phpmailer/mailer-for-os.php',         // for Linux execute
            'log_url'       => __DIR__ . '/../logs/mailer.$(date +%Y-%m-%d).log',   // for Linux execute
            'debug'         => 2,                         // Debug level to show client -> server and server -> client messages.
            'is_smtp'       => true,                      // Set mailer to use SMTP
            'host'          => 'mail.server.com',         // Specify main and backup SMTP servers
            'port'          => 587,                       // TCP port to connect to
            'smtp_auth'     => true,                      // Enable SMTP authentication
            'hostname'      => 'yourmailhostname.com',    // EHLO: setup email domain name
            'user_name'     => 'noreply@your-email-host.com',    // SMTP username
            'user_pw'       => 'your email server account password',    // SMTP password
            'smtp_secure'   => 'tls',                     // Enable TLS encryption, `ssl` also accepted
            'is_html'       => true,
            'templates'     => __DIR__ . '/../templates/email/'
        ],
        
        'db' => [
            'main' => [
                'host'     => 'mysql:host=127.0.0.1;port=3306;dbname=mem_access_main;charset=utf8mb4',
                'user'     => 'your_user_name',
                'password' => 'your_password',
                'option'   => [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                    //PDO::MYSQL_ATTR_SSL_KEY  => __DIR__ . '/../security/ssl/client-key.pem',
                    //PDO::MYSQL_ATTR_SSL_CERT => __DIR__ . '/../security/ssl/client-cert.pem',
                    //PDO::MYSQL_ATTR_SSL_CA   => __DIR__ . '/../security/ssl/server-ca.pem',
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
                ]
            ],

            // I suggest to save logs to a independent database server machine.
            'logger' => [
                'host'     => 'mysql:host=127.0.0.1;port=3306;dbname=mem_access_log;charset=utf8mb4',
                'user'     => 'your_user_name',
                'password' => 'your_password',
                'table'    => 'log',
                'option'   => [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                    //PDO::MYSQL_ATTR_SSL_KEY  => __DIR__ . '/../security/ssl/log/client-key.pem',
                    //PDO::MYSQL_ATTR_SSL_CERT => __DIR__ . '/../security/ssl/log/client-cert.pem',
                    //PDO::MYSQL_ATTR_SSL_CA   => __DIR__ . '/../security/ssl/log/server-ca.pem',
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
                ]
            ]
        ]
    ]
];
