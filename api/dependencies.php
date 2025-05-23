<?php
// DIC configuration
$container = $app->getContainer();
/**
 * monolog to mysql or text file. If mysql connection fail, it will switch to the text saving mode.
 */
$container['logger'] = function ( $c ) {
    $settings = $c->get('settings');
    //Create logger
    $logger = new Monolog\Logger( $settings['logger']['name'] ); // chanel name
    try {
        // Create MysqlHandler
        $pdo = Gn\Sql\SqlPdo::getInstance( $settings['db']['logger'] );
        $mySQLHandler = new MySQLHandler\MySQLHandler(
            $pdo,
            $settings['db']['logger'][ Gn\Lib\WazaariMySqlLog::LOG_TABLE_INDEX ],
            Gn\Lib\WazaariMySqlLog::WAZAARI_MYSQL_ADDITIONAL_FIELDS,
            $settings['logger']['level'],
            true,
            true
        );
        $logger->pushProcessor( function ( $record ) {
            $record['context'] = Gn\Lib\WazaariMySqlLog::getDefaultAdditionalFieldValues();
            return $record;
        } );
        $logger->pushHandler( $mySQLHandler );
    } catch ( PDOException $error ) {
        $logger->pushProcessor( new Monolog\Processor\WebProcessor() );
        $logger->pushHandler( new Monolog\Handler\StreamHandler( $settings['logger']['path'], $settings['logger']['level'] ) );
    }
    $logger->setTimezone( new DateTimeZone('UTC') ); // better use UTC
    return $logger;
};

// for PHPMailer
$container['mailer'] = function ( $c ) {
    $settings = $c->get( 'settings' );
    return new Gn\MailerCtl( $settings['mailer'] );
};

/**
 * For Tuupla PSR-7 JWT Authentication Middleware.
 */
$container['jwt'] = function ($c) {
    return new StdClass;
};

/**
 * For saving the member information about the decoded JWT
 */
$container['usr'] = function ($c) {
    return new StdClass;
};
