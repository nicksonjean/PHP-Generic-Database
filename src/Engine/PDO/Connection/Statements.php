<?php

namespace GenericDatabase\Engine\PDO\Connection;

use PDO;
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

    public static function internalFetchBoth($statement = null)
    {
        return $statement->fetch(PDO::FETCH_BOTH);
    }

    public static function internalFetchAssoc($statement = null)
    {
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public static function internalFetchNum($statement = null)
    {
        return $statement->fetch(PDO::FETCH_NUM);
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
