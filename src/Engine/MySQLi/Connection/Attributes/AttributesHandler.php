<?php

namespace GenericDatabase\Engine\MySQLi\Connection\Attributes;

use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\MySQLi\Connection\MySQL;

class AttributesHandler extends AbstractAttributes implements IAttributes
{
    private static int $fetchMode = MYSQLI_BOTH;

    private static int $errorMode = MYSQLI_REPORT_ERROR;

    private static array $variables = [];

    private static array $charsets = [];

    private static array $collations = [];

    private static array $settings = [];

    private const RX_ERROR = "[%d] %s\n";

    public const CLIENT = 0;

    public const RESULTS = 1;

    public const CONNECTION = 2;

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
    public function getCharsetType(int $type): string
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
    public function getInverseCharsetType(int $type): string
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
    public function settings(): array
    {
        $this->setFetchMode();
        $this->setErrorMode();
        $this->setVariables();
        $this->setCharacterSet();
        $this->setCollation();
        $this->setSettings();
        return $this->getSettings();
    }

    /**
     * @desc Optionally set the return mode.
     *
     * @param int|null $type = null
     * @noinspection PhpSameParameterValueInspection
     */
    private function setFetchMode(?int $type = null): void
    {
        self::$fetchMode = match ($type) {
            1 => MYSQLI_NUM,
            2 => MYSQLI_ASSOC,
            default => MYSQLI_BOTH,
        };
    }

    private function setErrorMode(): void
    {
        self::$errorMode = ($this->get('exception')) ? MYSQLI_REPORT_ERROR : MYSQLI_REPORT_OFF;
        if ($this->get('exception')) {
            mysqli_report(self::$errorMode);
        }
    }

    private function setVariables(): void
    {
        if (!($res = $this->getInstance()->getConnection()->query("SHOW VARIABLES LIKE '%character%'"))) {
            printf(
                self::RX_ERROR,
                $this->getInstance()->getConnection()->errno,
                $this->getInstance()->getConnection()->error
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
    private function getVariables(?int $type = self::CONNECTION)
    {
        return !is_null($type) ? self::$variables[$this->getInverseCharsetType($type)] : self::$variables;
    }

    /**
     * @throws Exceptions
     */
    private function setCharacterSet(): void
    {
        $character = sprintf("SHOW CHARACTER SET LIKE '%s'", self::$variables[self::getCharsetType(self::CONNECTION)]);
        if (!($res = $this->getInstance()->getConnection()->query($character)) || !(self::$charsets = $res->fetch_assoc())) {
            vprintf(
                self::RX_ERROR,
                [
                    $this->getInstance()->getConnection()->errno,
                    $this->getInstance()->getConnection()->error
                ]
            );
            return;
        }
        self::$variables[$this->getInverseCharsetType(self::CONNECTION)] = [
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

    private function getCharacterSet(): array
    {
        return self::$charsets;
    }

    /**
     * @throws Exceptions
     */
    private function setCollation(): void
    {
        $collation = sprintf("SHOW COLLATION LIKE '%s'", $this->getCharacterSet()['Default collation']);
        if (!($res = $this->getInstance()->getConnection()->query($collation)) || !(self::$collations = $res->fetch_assoc())) {
            vprintf(
                self::RX_ERROR,
                [
                    $this->getInstance()->getConnection()->errno,
                    $this->getInstance()->getConnection()->error
                ]
            );
            $this->getCollation();
            return;
        }
        self::$variables[$this->getInverseCharsetType(self::CONNECTION)]['sortlen'] = $this->getCollation()['Sortlen'];
        self::$variables[$this->getInverseCharsetType(self::CONNECTION)]['default'] = $this->getCollation()['Default'];
        self::$variables[$this->getInverseCharsetType(self::CONNECTION)]['compiled'] = $this->getCollation()['Compiled'];
        self::$variables[$this->getInverseCharsetType(self::CONNECTION)]['id'] = $this->getCollation()['Id'];
        $res->free_result();
    }

    private function getCollation(): array
    {
        return self::$collations;
    }

    private function setSettings(): void
    {
        $query = "SHOW SESSION VARIABLES WHERE Variable_name IN('autocommit', 'lower_case_table_names', 'sql_mode', 'connect_timeout', 'interactive_timeout', 'wait_timeout', 'net_read_timeout', 'net_write_timeout');";
        if (!($res = $this->getInstance()->getConnection()->query($query))) {
            vprintf(
                self::RX_ERROR,
                [
                    $this->getInstance()->getConnection()->errno,
                    $this->getInstance()->getConnection()->error
                ]
            );
            $this->getSettings();
            return;
        }
        while ($row = $res->fetch_assoc()) {
            self::$settings[$row['Variable_name']] = $row['Value'];
        }
        $res->free_result();
    }

    private function getSettings(): array
    {
        return self::$settings;
    }

    /** @noinspection PhpUnused */
    private function connectionStatus(): string
    {
        return (Compare::connection($this->getInstance()->getConnection()) === 'mysqli')
            ? sprintf(
                'Connection OK in %s; waiting to send.',
                $this->getInstance()->getConnection()->host_info
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
    public function define(?int $type = self::CONNECTION): void
    {
        $settings = $this->settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT' => (bool) $this->getOptionsHandler()->getOptions(MySQL::ATTR_AUTOCOMMIT),
                'ERRMODE' => self::$errorMode,
                'CASE' => (int) $settings['lower_case_table_names'] === 1 ? 0 : 1,
                'CLIENT_VERSION' => $this->getInstance()->getConnection()->client_info,
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool) $this->getOptionsHandler()->getOptions(MySQL::ATTR_PERSISTENT),
                'SERVER_INFO' => $this->getInstance()->getConnection()->stat(),
                'SERVER_VERSION' => $this->getInstance()->getConnection()->server_info,
                'TIMEOUT' => (int) $settings['connect_timeout'],
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(MySQL::ATTR_DEFAULT_FETCH_MODE)
                    ?? self::$fetchMode
                    ?: MySQL::FETCH_BOTH,
                'CHARACTER_SET' => $this->getVariables($type)['charset'],
                'COLLATION' => $this->getVariables($type)['collation'],
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
