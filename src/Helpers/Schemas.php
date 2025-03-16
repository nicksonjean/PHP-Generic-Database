<?php

namespace GenericDatabase\Helpers;

use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Shared\Objectable;

/**
 * The `GenericDatabase\Helpers\Schemas` class is a helper class that uses the Objectable trait and allows dynamic properties. It has a single static method makeArgs.
 *
 * Method:
 * - `makeArgs(array $params): object:` This method makes an arguments list
 *
 * @property mixed|null $statement
 * @property mixed|null $query
 * @property mixed|null $by
 * @property mixed|null $is
 * @package GenericDatabase\Helpers
 * @subpackage Schemas
 */
class Schemas
{
    use Objectable;

    /**
     * This method makes an arguments list
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
