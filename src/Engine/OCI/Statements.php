<?php

namespace GenericDatabase\Engine\OCI;

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
        $fetchArgument = $constructorArguments === null ? [] : $constructorArguments;
        if (is_array($rowData)) {
            return Reflections::createObjectAndSetPropertiesCaseInsensitive($aClassOrObject, $fetchArgument, $rowData);
        }
        return $rowData;
    }

    public static function internalFetchBoth($statement = null): bool|array
    {
        return oci_fetch_array($statement, OCI_BOTH | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
    }

    public static function internalFetchAssoc($statement = null): bool|array
    {
        return oci_fetch_assoc($statement);
    }

    public static function internalFetchNum($statement = null): bool|array
    {
        return oci_fetch_row($statement);
    }

    public static function internalFetchColumn($statement = null, $columnIndex = 0)
    {
        $row = oci_fetch_array($statement, OCI_NUM | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
        $fetchArgument = $columnIndex === null ? 0 : $columnIndex;
        return $row[$fetchArgument] ?? null;
    }

    public static function internalFetchAllAssoc($statement = null): array
    {
        $result = [];
        oci_fetch_all(
            $statement,
            $result,
            0,
            -1,
            OCI_FETCHSTATEMENT_BY_ROW | OCI_RETURN_NULLS | OCI_RETURN_LOBS
        );
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
        $fetchArgument = $columnIndex === null ? 0 : $columnIndex;
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
        $fetchArgument = $constructorArguments === null ? [] : $constructorArguments;
        while ($row = self::internalFetchClassOrObject($statement, $fetchArgument, $aClassOrObject)) {
            $result[] = $row;
        }
        return $result;
    }
}
