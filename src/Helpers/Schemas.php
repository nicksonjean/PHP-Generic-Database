<?php

namespace GenericDatabase\Helpers;

use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Shared\Objectable;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class Schemas
{
    use Objectable;

    /**
     * This function makes an arguments list
     *
     * @param array $params Arguments list
     * @return object
     */
    public static function makeArgs(array $params): object
    {
        $index = ['isArgs' => 1, 'isMulti' => 2, 'isNamed' => 3];
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
                $sqlArgs = SQL::arguments($params[$index['isArgs']], array_slice($params, $index['isMulti']));
            }
        }
        $result = new self();
        $result->statement->object = reset($params);
        $result->statement->name = array_key_exists($index['isNamed'], $params) ? $params[$index['isNamed']] : '';
        $result->query->string = array_key_exists($index['isArgs'], $params) ? $params[$index['isArgs']] : '';
        $result->query->arguments = $sqlArgs ?? [];
        $result->by->array = $isArray ?? false;
        $result->by->arguments = $isArgs ?? false;
        $result->is->array->multi = $isMulti ?? false;
        return $result;
    }
}
