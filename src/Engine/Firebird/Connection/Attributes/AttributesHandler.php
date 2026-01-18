<?php

namespace GenericDatabase\Engine\Firebird\Connection\Attributes;

use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\Firebird\Connection\Firebird;

class AttributesHandler extends AbstractAttributes implements IAttributes
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

    private function settings(): ?array
    {
        $service = $this->attachService();
        if ($service === false) {
            return null;
        }
        $results = $this->getDatabaseInfo($service);
        $serverInfo = $this->getServerInfo($service, $results['ods_version']);
        ibase_service_detach($service);
        return [
            ...$results,
            ...$serverInfo
        ];
    }

    private function attachService()
    {
        return ibase_service_attach(
            vsprintf(
                "%s/%s",
                [
                    $this->get('host'),
                    $this->get('port')
                ]
            ),
            $this->get('user'),
            $this->get('password')
        );
    }

    private function getDatabaseInfo($service): array
    {
        $info = ibase_db_info($service, $this->get('database'), 4);
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

    private function getServerInfo($service, $odsVersion): array
    {
        $server = vsprintf(
            '%s %s %s on disk structure version %s',
            [
                ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (access method), version "' . ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '"',
                ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (remote method), version "' . ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '/tcp (' . gethostname() . ')/P15:C"',
                ibase_server_info($service, IBASE_SVC_IMPLEMENTATION) . ' (remote interface), version "' . ibase_server_info($service, IBASE_SVC_SERVER_VERSION) . '/tcp (' . gethostname() . ')/P15:C"',
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
    private function connectionStatus(): string
    {
        return (Compare::connection($this->getInstance()->getConnection()) === 'firebird/ibase')
            ? sprintf(
                'Connection OK in %s via TCP/IP; waiting to send.',
                $this->get('host')
            )
            : 'Connection failed;';
    }

    /**
     * Define all Firebird attribute of the connection a ready exist
     *
     * @return void
     * @throws Exceptions
     */
    public function define(): void
    {
        $settings = $this->settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT' => (bool)$this->getOptionsHandler()->getOptions(Firebird::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['server_version'],
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)$this->getOptionsHandler()->getOptions(Firebird::ATTR_PERSISTENT),
                'SERVER_INFO', 'SERVER_VERSION' => $settings['server_info'],
                'TIMEOUT' =>  (int) $this->getOptionsHandler()->getOptions(Firebird::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(Firebird::ATTR_DEFAULT_FETCH_MODE) ?? Firebird::FETCH_BOTH,
                'CHARACTER_SET' => $this->get('charset'),
                'COLLATION' => $this->get('charset') == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
