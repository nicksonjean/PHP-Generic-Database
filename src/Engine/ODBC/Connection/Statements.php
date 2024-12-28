<?php

namespace GenericDatabase\Engine\ODBC\Connection;

use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class Statements
{
    /**
     * @throws ReflectionException
     */
    public static function internalFetchClass(
        $statement = null,
        $constructorArguments = [],
        $aClassOrObject = '\stdClass',
    ) {
        $rowData = self::internalFetchAssoc($statement);
        $fetchArgument = $constructorArguments ?? [];
        if (is_array($rowData)) {
            return Reflections::createObjectAndSetPropertiesCaseInsensitive($aClassOrObject, $fetchArgument, $rowData);
        }
        return $rowData;
    }

    public static function internalFetchBoth($statement = null): false|array
    {
        if (!odbc_fetch_row($statement)) {
            return false;
        }
        $row = [];
        $subfields = odbc_num_fields($statement);
        for ($i = 1; $i <= $subfields; $i++) {
            $result = odbc_result($statement, $i);
            if (mb_detect_encoding($result, 'utf8', true) === false) {
                $resultFixed = ODBC::setType(mb_convert_encoding($result, 'utf8', 'ISO-8859-1'));
                $row[odbc_field_name($statement, $i)] = $row[$i - 1] = $resultFixed;
            } else {
                $row[odbc_field_name($statement, $i)] = $row[$i - 1] = ODBC::setType($result);
            }
        }
        return $row;
    }

    public static function internalFetchAssoc($statement = null): false|array
    {
        if (!odbc_fetch_row($statement)) {
            return false;
        }
        $row = [];
        $subfields = odbc_num_fields($statement);
        for ($i = 1; $i <= $subfields; $i++) {
            $result = odbc_result($statement, $i);
            if (mb_detect_encoding($result, 'utf8', true) === false) {
                $resultFixed = ODBC::setType(mb_convert_encoding($result, 'utf8', 'ISO-8859-1'));
                $row[odbc_field_name($statement, $i)] = $resultFixed;
            } else {
                $row[odbc_field_name($statement, $i)] = ODBC::setType($result);
            }
        }
        return $row;
    }

    public static function internalFetchNum($statement = null): false|array
    {
        if (!odbc_fetch_row($statement)) {
            return false;
        }
        $row = [];
        $subfields = odbc_num_fields($statement);
        for ($i = 1; $i <= $subfields; $i++) {
            $result = odbc_result($statement, $i);
            if (mb_detect_encoding($result, 'utf8', true) === false) {
                $resultFixed = ODBC::setType(mb_convert_encoding($result, 'utf8', 'ISO-8859-1'));
                $row[$i - 1] = $resultFixed;
            } else {
                $row[$i - 1] = ODBC::setType($result);
            }
        }
        return $row;
    }

    public static function internalFetchColumn($statement = null, $columnIndex = 0)
    {
        $rowData = self::internalFetchNum($statement);
        $fetchArgument = $columnIndex ?? 0;
        if (is_array($rowData)) {
            return $rowData[$fetchArgument] ?? null;
        }
        return false;
    }

    public static function internalFetchAllAssoc($statement = null): false|array
    {
        $result = [];
        while ($data = self::internalFetchAssoc($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    /** @noinspection PhpUnused */
    public static function internalFetchAllNum($statement = null): array
    {
        $result = [];
        while ($data = self::internalFetchNum($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    /** @noinspection PhpUnused */
    public static function internalFetchAllBoth($statement = null): array
    {
        $result = [];
        while ($data = self::internalFetchBoth($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    /** @noinspection PhpUnused */
    public static function internalFetchAllColumn($statement = null, $columnIndex = 0): array
    {
        $result = [];
        $fetchArgument = $columnIndex ?? 0;
        while ($data = self::internalFetchColumn($statement, $fetchArgument)) {
            $result[] = $data;
        }
        return $result;
    }

    /**
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    public static function internalFetchAllClass(
        $statement = null,
        $constructorArguments = [],
        $aClassOrObject = '\stdClass',
    ): array {
        $result = [];
        $fetchArgument = $constructorArguments ?? [];
        while ($row = self::internalFetchClass($statement, $fetchArgument, $aClassOrObject)) {
            $result[] = $row;
        }
        return $result;
    }
}
