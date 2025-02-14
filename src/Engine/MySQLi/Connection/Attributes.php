<?php

namespace GenericDatabase\Engine\MySQLi\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;

#[AllowDynamicProperties]
class Attributes
{
    private static int $fetchMode = MYSQLI_BOTH;

    private static int $errorMode = MYSQLI_REPORT_ERROR;

    private static array $variables = [];

    private static array $charsets = [];

    private static array $collations = [];

    private static array $settings = [];

    private const RX_ERROR = "[%d] %s\n";

    final public const CLIENT = 0;

    final public const RESULTS = 1;

    final public const CONNECTION = 2;

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

    /**
     * @throws Exceptions
     */
    public static function getCharsetType(int $type): string
    {
        return match ($type) {
            self::CLIENT => 'character_set_client',
            self::RESULTS => 'character_set_results',
            self::CONNECTION => 'character_set_connection',
            default => throw new Exceptions("Invalid type: $type"),
        };
    }

    /**
     * @throws Exceptions
     */
    public static function getInverseCharsetType(int $type): string
    {
        return match ($type) {
            self::CLIENT => 'client',
            self::RESULTS => 'results',
            self::CONNECTION => 'connection',
            default => throw new Exceptions("Invalid type: $type"),
        };
    }

    /**
     * @throws Exceptions
     */
    public static function settings(): array
    {
        self::setFetchMode();
        self::setErrorMode();
        self::setVariables();
        self::setCharacterSet();
        self::setCollation();
        self::setSettings();
        return self::getSettings();
    }

    /**
     * @desc Optionally set the return mode.
     *
     * @param int|null $type = null
     * @noinspection PhpSameParameterValueInspection
     */
    private static function setFetchMode(int $type = null): void
    {
        self::$fetchMode = match ($type) {
            1 => MYSQLI_NUM,
            2 => MYSQLI_ASSOC,
            default => MYSQLI_BOTH,
        };
    }

    private static function setErrorMode(): void
    {
        self::$errorMode = (MySQLiConnection::getInstance()->getException()) ? MYSQLI_REPORT_ERROR : MYSQLI_REPORT_OFF;
        if (MySQLiConnection::getInstance()->getException()) {
            mysqli_report(self::$errorMode);
        }
    }

    private static function setVariables(): void
    {
        if (!($res = MySQLiConnection::getInstance()->getConnection()->query("SHOW VARIABLES LIKE '%character%'"))) {
            printf(
                self::RX_ERROR,
                MySQLiConnection::getInstance()->getConnection()->errno,
                MySQLiConnection::getInstance()->getConnection()->error
            );
            return;
        }

        while ($row = $res->fetch_assoc()) {
            self::$variables[$row['Variable_name']] = $row['Value'];
        }

        $res->free_result();
    }

    /**
     * @throws Exceptions
     * @noinspection PhpUnused
     */
    private static function getVariables(?int $type = self::CONNECTION)
    {
        return !is_null($type) ? self::$variables[self::getInverseCharsetType($type)] : self::$variables;
    }

    /**
     * @throws Exceptions
     */
    private static function setCharacterSet(): void
    {
        if (
            !($res = MySQLiConnection::getInstance()->getConnection()->query(
                sprintf(
                    "SHOW CHARACTER SET LIKE '%s'",
                    self::$variables[self::getCharsetType(self::CONNECTION)]
                )
            )
            ) ||
            !(self::$charsets = $res->fetch_assoc())
        ) {
            printf(
                self::RX_ERROR,
                MySQLiConnection::getInstance()->getConnection()->errno,
                MySQLiConnection::getInstance()->getConnection()->error
            );
            return;
        }

        self::$variables[self::getInverseCharsetType(self::CONNECTION)] = [
            'charset' => self::$charsets['Charset'],
            'description' => self::$charsets['Description'],
            'collation' => self::$charsets['Default collation'],
            'maxlen' => self::$charsets['Maxlen'],
            'sortlen' => null,
            'default' => null,
            'compiled' => null,
            'id' => null
        ];

        $res->free_result();
    }

    private static function getCharacterSet(): array
    {
        return self::$charsets;
    }

    /**
     * @throws Exceptions
     */
    private static function setCollation(): void
    {
        if (
            !($res = MySQLiConnection::getInstance()->getConnection()->query(
                sprintf(
                    "SHOW COLLATION LIKE '%s'",
                    self::getCharacterSet()['Default collation']
                )
            )
            ) ||
            !(self::$collations = $res->fetch_assoc())
        ) {
            printf(
                self::RX_ERROR,
                MySQLiConnection::getInstance()->getConnection()->errno,
                MySQLiConnection::getInstance()->getConnection()->error
            );
            self::getCollation();
            return;
        }

        self::$variables[self::getInverseCharsetType(self::CONNECTION)]['sortlen'] = self::getCollation()['Sortlen'];
        self::$variables[self::getInverseCharsetType(self::CONNECTION)]['default'] = self::getCollation()['Default'];
        self::$variables[self::getInverseCharsetType(self::CONNECTION)]['compiled'] = self::getCollation()['Compiled'];
        self::$variables[self::getInverseCharsetType(self::CONNECTION)]['id'] = self::getCollation()['Id'];

        $res->free_result();
    }

    private static function getCollation(): array
    {
        return self::$collations;
    }

    private static function setSettings(): void
    {
        $query = "SHOW SESSION VARIABLES WHERE Variable_name IN(
            'autocommit',
            'lower_case_table_names',
            'sql_mode',
            'connect_timeout',
            'interactive_timeout',
            'wait_timeout',
            'net_read_timeout',
            'net_write_timeout');";
        if (!($res = MySQLiConnection::getInstance()->getConnection()->query($query))) {
            printf(
                self::RX_ERROR,
                MySQLiConnection::getInstance()->getConnection()->errno,
                MySQLiConnection::getInstance()->getConnection()->error
            );
            self::getSettings();
            return;
        }

        while ($row = $res->fetch_assoc()) {
            self::$settings[$row['Variable_name']] = $row['Value'];
        }

        $res->free_result();
    }

    private static function getSettings(): array
    {
        return self::$settings;
    }

    /** @noinspection PhpUnused */
    private static function connectionStatus(): string
    {
        return (Compare::connection(MySQLiConnection::getInstance()->getConnection()) === 'mysqli')
            ? sprintf(
                'Connection OK in %s; waiting to send.',
                MySQLiConnection::getInstance()->getConnection()->host_info
            )
            : 'Connection failed;';
    }

    /**
     * Define all MySQLi attribute of the connection a ready exist
     *
     * @param int|null $type
     * @return void
     * @throws Exceptions
     */
    public static function define(?int $type = self::CONNECTION): void
    {
        $settings = self::settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT' => (bool) Options::getOptions(MySQL::ATTR_AUTOCOMMIT),
                'ERRMODE' => self::$errorMode,
                'CASE' => (int) $settings['lower_case_table_names'] === 1 ? 0 : 1,
                'CLIENT_VERSION' => MySQLiConnection::getInstance()->getConnection()->client_info,
                'CONNECTION_STATUS' => self::connectionStatus(),
                'PERSISTENT' => (bool) Options::getOptions(MySQL::ATTR_PERSISTENT),
                'SERVER_INFO' => MySQLiConnection::getInstance()->getConnection()->stat(),
                'SERVER_VERSION' => MySQLiConnection::getInstance()->getConnection()->server_info,
                'TIMEOUT' => (int) $settings['connect_timeout'],
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(MySQL::ATTR_DEFAULT_FETCH_MODE)
                    ?? self::$fetchMode
                    ?: MySQL::FETCH_BOTH,
                'CHARACTER_SET' => self::getVariables($type)['charset'],
                'COLLATION' => self::getVariables($type)['collation'],
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        MySQLiConnection::getInstance()->setAttributes($result);
    }
}
