<?php

namespace GenericDatabase\Engine\SQLSrv;

use GenericDatabase\Helpers\Reflections;

class Statements
{
    public static function internalFetchClassOrObject(
        $statement = null,
        $constructorArguments = [],
        $aClassOrObject = '\stdClass',
    ) {
        $rowData = self::internalFetchAssoc($statement);
        $fetchArgument = $constructorArguments === null ? [] : $constructorArguments;
        if (is_array($rowData)) {
            return Reflections::createObjectAndSetPropertiesCaseInsensitive($aClassOrObject, $fetchArgument, $rowData);
        }
        return $rowData;
    }

    public static function internalFetchBoth($statement = null)
    {
        return sqlsrv_fetch_array($statement, SQLSRV_FETCH_BOTH);
    }

    public static function internalFetchAssoc($statement = null)
    {
        return sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
    }

    public static function internalFetchNum($statement = null)
    {
        return sqlsrv_fetch_array($statement, SQLSRV_FETCH_NUMERIC);
    }

    public static function internalFetchColumn($statement = null, $columnIndex = 0)
    {
        $rowData = self::internalFetchNum($statement);
        $fetchArgument = $columnIndex === null ? 0 : $columnIndex;
        if (is_array($rowData)) {
            return isset($rowData[$fetchArgument]) ? $rowData[$fetchArgument] : null;
        }
        return false;
    }

    public static function internalFetchAllAssoc($statement = null)
    {
        $result = [];
        while ($data = self::internalFetchAssoc($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    public static function internalFetchAllNum($statement = null)
    {
        $result = [];
        while ($data = self::internalFetchNum($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    public static function internalFetchAllBoth($statement = null)
    {
        $result = [];
        while ($data = self::internalFetchBoth($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    public static function internalFetchAllColumn($statement = null, $columnIndex = 0)
    {
        $result = [];
        $fetchArgument = $columnIndex === null ? 0 : $columnIndex;
        while ($data = self::internalFetchColumn($statement, $fetchArgument)) {
            $result[] = $data;
        }
        return $result;
    }

    public static function internalFetchAllClassOrObjects(
        $statement = null,
        $constructorArguments = [],
        $aClassOrObject = '\stdClass',
    ) {
        $result = [];
        $fetchArgument = $constructorArguments === null ? [] : $constructorArguments;
        while ($row = self::internalFetchClassOrObject($statement, $fetchArgument, $aClassOrObject)) {
            if ($row !== false) {
                $result[] = $row;
            }
        }
        return $result;
    }
}
