<?php

namespace GenericDatabase\Engine\PgSQL;

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
        return pg_fetch_array($statement);
    }

    public static function internalFetchAssoc($statement = null)
    {
        return pg_fetch_assoc($statement);
    }

    public static function internalFetchNum($statement = null)
    {
        return pg_fetch_row($statement);
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
        return pg_fetch_all($statement);
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
        $fetchArgument = $columnIndex === null ? 0 : $columnIndex;
        return pg_fetch_all_columns($statement, $fetchArgument);
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
