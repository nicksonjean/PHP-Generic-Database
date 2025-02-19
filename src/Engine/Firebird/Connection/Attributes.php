<?php

namespace GenericDatabase\Engine\Firebird\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\FirebirdConnection;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;

#[AllowDynamicProperties]
class Attributes
{
    /**
     * static attributes constants
     *
     */
    public static array $attributeList = [
        'AUTOCOMMIT',
        'ERRMODE',
        'CASE',
        'CLIENT_VERSION',
        'CONNECTION_STATUS',
        'PERSISTENT',
        'SERVER_INFO',
        'SERVER_VERSION',
        'TIMEOUT',
        'EMULATE_PREPARES',
        'DEFAULT_FETCH_MODE',
        'CHARACTER_SET',
        'COLLATION'
    ];

    private static function settings(): ?array
    {
        $service = self::attachService();
        if ($service === false) {
            return null;
        }
        $results = self::getDatabaseInfo($service);
        $serverInfo = self::getServerInfo($service, $results['ods_version']);
        ibase_service_detach($service);
        return [
            ...$results,
            ...$serverInfo
        ];
    }

    private static function attachService()
    {
        return ibase_service_attach(
            sprintf(
                "%s/%s",
                FirebirdConnection::getInstance()->getHost(),
                FirebirdConnection::getInstance()->getPort()
            ),
            FirebirdConnection::getInstance()->getUser(),
            FirebirdConnection::getInstance()->getPassword()
        );
    }

    private static function getDatabaseInfo($service): array
    {
        $info = ibase_db_info($service, FirebirdConnection::getInstance()->getDatabase(), 4);
        $matches = [];
        preg_match('/information:\s(.*)\sVariable/s', $info, $matches);
        $results = [];
        $lines = preg_split("/((\r?\n)|(\r\n?))/", trim($matches[1]));
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 0) {
                [$name, $value] = explode('|', $line . '|', 2);
                $name = str_replace(' ', '_', mb_strtolower(trim($name)));
                $results[$name] = trim($value) ?: null;
            }
        }
        if (!isset($results['ods_version'])) {
            $results['ods_version'] = null;
        }
        return $results;
    }

    private static function getServerInfo($service, $odsVersion): array
    {
        $server = vsprintf(
            '%s %s %s on disk structure version %s',
            [
                ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (access method), version "' .
                    ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '"',
                ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (remote method), version "' .
                    ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '/tcp (' . gethostname() . ')/P15:C"',
                ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (remote interface), version "' .
                    ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '/tcp (' . gethostname() . ')/P15:C"',
                $odsVersion
            ]
        );
        return [
            'server_version' => ibase_server_info($service, IBASE_SVC_SERVER_VERSION),
            'server_implementation' => ibase_server_info($service, IBASE_SVC_IMPLEMENTATION),
            'server_users' => ibase_server_info($service, IBASE_SVC_GET_USERS),
            'server_directory' => ibase_server_info($service, IBASE_SVC_GET_ENV),
            'server_lock_path' => ibase_server_info($service, IBASE_SVC_GET_ENV_LOCK),
            'server_lib_path' => ibase_server_info($service, IBASE_SVC_GET_ENV_MSG),
            'user_database_path' => ibase_server_info($service, IBASE_SVC_USER_DBPATH),
            'database_info' => ibase_server_info($service, IBASE_SVC_SVR_DB_INFO),
            'server_info' => $server
        ];
    }

    /** @noinspection PhpUnused */
    private static function connectionStatus(): string
    {
        return (Compare::connection(FirebirdConnection::getInstance()->getConnection()) === 'firebird/ibase')
            ? sprintf('Connection OK in %s via TCP/IP; waiting to send.', FirebirdConnection::getInstance()->getHost())
            : 'Connection failed;';
    }

    /**
     * Define all Firebird attribute of the connection a ready exist
     *
     * @return void
     * @throws Exceptions
     */
    public static function define(): void
    {
        $settings = self::settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT' => (bool)Options::getOptions(Firebird::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['server_version'],
                'CONNECTION_STATUS' => self::connectionStatus(),
                'PERSISTENT' => (bool)Options::getOptions(Firebird::ATTR_PERSISTENT),
                'SERVER_INFO', 'SERVER_VERSION' => $settings['server_info'],
                'TIMEOUT' =>  (int) Options::getOptions(Firebird::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(Firebird::ATTR_DEFAULT_FETCH_MODE) ?? Firebird::FETCH_BOTH,
                'CHARACTER_SET' => FirebirdConnection::getInstance()->getCharset(),
                'COLLATION' => FirebirdConnection::getInstance()->getCharset() == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        FirebirdConnection::getInstance()->setAttributes($result);
    }
}
