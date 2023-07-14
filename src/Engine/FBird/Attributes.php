<?php

namespace GenericDatabase\Engine\FBird;

use AllowDynamicProperties;
use GenericDatabase\Engine\FBirdEngine;
use GenericDatabase\Engine\FBird\Options;
use GenericDatabase\Helpers\GenericException;

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
                FBirdEngine::getInstance()->getHost(),
                FBirdEngine::getInstance()->getPort()
            ),
            FBirdEngine::getInstance()->getUser(),
            FBirdEngine::getInstance()->getPassword()
        );
    }

    private static function getDatabaseInfo($service): array
    {
        $info = ibase_db_info($service, FBirdEngine::getInstance()->getDatabase(), 4);
        $matches = [];
        preg_match('/information:\s(.*)\sVariable/s', $info, $matches);
        $results = [];
        $lines = preg_split("/((\r?\n)|(\r\n?))/", trim($matches[1]));
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 0) {
                [$name, $value] = explode('|', $line . '|', 2);
                $name = str_replace(' ', '_', strtolower(trim($name)));
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

    /**
     * Define all FBird attibute of the conection a ready exist
     *
     * @return void
     * @throws GenericException
     */
    public static function define(): void
    {
        $settings = self::settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT', 'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['server_version'],
                'CONNECTION_STATUS' => FBirdEngine::getInstance()->getConnection()
                    ? 'Connection OK; waiting to send.'
                    : 'Connection failed;',
                'PERSISTENT' => (int) !Options::getOptions(FBird::ATTR_PERSISTENT)
                    ? 0
                    : (int) Options::getOptions(FBird::ATTR_PERSISTENT),
                'SERVER_INFO', 'SERVER_VERSION' => $settings['server_info'],
                'TIMEOUT' =>  (int) Options::getOptions(FBird::ATTR_CONNECT_TIMEOUT)
                    ? Options::getOptions(FBird::ATTR_CONNECT_TIMEOUT)
                    : 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => 3,
                'CHARACTER_SET' => FBirdEngine::getInstance()->getCharset(),
                'COLLATION' => FBirdEngine::getInstance()->getCharset() == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new GenericException("Invalid attribute: $attribute"),
            };
        }
        FBirdEngine::getInstance()->setAttributes($result);
    }
}
