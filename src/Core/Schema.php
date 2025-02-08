<?php

namespace GenericDatabase\Core;

use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\Translate;
use stdClass;

class Schema
{
    /**
     * This function makes an arguments list
     *
     * @param string $driver
     * @param array $params Arguments list
     * @return object
     */
    public static function makeArgs(array $params): object
    {
        $index = ['isMulti' => 2, 'isArgs' => 1];
        if (array_key_exists($index['isMulti'], $params)) {
            if (is_array($params[$index['isMulti']])) {
                $isArgs = false;
                $isArray = true;
                $isMulti = Arrays::isMultidimensional($params[$index['isMulti']]);
                $sqlArgs = $params[$index['isMulti']];
            } else {
                $isArgs = true;
                $isArray = false;
                $isMulti = false;
                $sqlArgs = Translate::arguments($params[$index['isArgs']], array_slice($params, $index['isMulti']));
            }
        }
        $metadata = new stdClass();
        $metadata->sqlStatement = reset($params);
        $metadata->sqlQuery = $params[1];
        $metadata->sqlArgs = $sqlArgs ?? [];
        $metadata->isArray = $isArray ?? false;
        $metadata->isMulti = $isMulti ?? false;
        $metadata->isArgs = $isArgs ?? false;
        return $metadata;
    }
}
