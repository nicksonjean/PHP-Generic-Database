<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class Statements
{
    /**
     * @throws ReflectionException
     */
    public static function internalFetchClassOrObject(
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

    public static function internalFetchBoth($statement = null): bool|array
    {
        $tmpData = mysqli_fetch_assoc($statement);
        if (is_array($tmpData)) {
            return Arrays::assocToIndexCombine($tmpData);
        }
        return false;
    }

    public static function internalFetchAssoc($statement = null): bool|array|null
    {
        return mysqli_fetch_assoc($statement);
    }

    public static function internalFetchNum($statement = null): bool|array|null
    {
        return mysqli_fetch_row($statement);
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

    public static function internalFetchAllAssoc($statement = null): array
    {
        $result = [];
        while ($data = self::internalFetchAssoc($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    public static function internalFetchAllNum($statement = null): array
    {
        $result = [];
        while ($data = self::internalFetchNum($statement)) {
            $result[] = $data;
        }
        return $result;
    }

    public static function internalFetchAllBoth($statement = null): array
    {
        $result = [];
        while ($data = self::internalFetchBoth($statement)) {
            $result[] = $data;
        }
        return $result;
    }

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
     */
    public static function internalFetchAllClassOrObjects(
        $statement = null,
        $constructorArguments = [],
        $aClassOrObject = '\stdClass',
    ): array {
        $result = [];
        $fetchArgument = $constructorArguments ?? [];
        while ($row = self::internalFetchClassOrObject($statement, $fetchArgument, $aClassOrObject)) {
            $result[] = $row;
        }
        return $result;
    }
}
