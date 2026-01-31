<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\INI\QueryBuilder;

use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Core\Table;
use GenericDatabase\Core\Column;
use GenericDatabase\Core\Junction;
use GenericDatabase\Core\Sorting;
use GenericDatabase\Core\Grouping;
use GenericDatabase\Core\Limit;
use GenericDatabase\Core\Where;
use GenericDatabase\Core\Condition;
use GenericDatabase\Interfaces\QueryBuilder\ICriteria;

/**
 * Criteria class for INI QueryBuilder.
 * Handles parsing and processing of query criteria.
 *
 * @package GenericDatabase\Engine\INI\QueryBuilder
 */
class Criteria implements ICriteria
{
    /**
     * Parse SELECT criteria.
     *
     * @param array $arguments The arguments.
     * @return array
     */
    public static function getSelect(array $arguments): array
    {
        $result = [];
        $data = array_key_exists('data', $arguments) ? trim($arguments['data']) : [];
        if (preg_match(Regex::getSelect(), $data, $matches)) {
            $result = $matches['function_name'] ? Arrays::arraySafe([
                'type' => Column::FUNCTION(),
                'value' => trim($data),
                'function' => $matches['function_name'],
                'arguments' => $matches['function_arguments'],
                'alias' => $matches['function_column_alias'] ?? null,
            ]) : Arrays::arraySafe([
                'type' => Column::METADATA(),
                'value' => trim($data),
                'prefix' => $matches['table_prefix'] ?? null,
                'column' => $matches['column_name'],
                'alias' => $matches['column_alias'] ?? null,
            ]);
        }
        return $result;
    }

    /**
     * Parse FROM criteria.
     *
     * @param array $arguments The arguments.
     * @return array
     */
    public static function getFrom(array $arguments): array
    {
        $result = [];
        $type = array_key_exists('type', $arguments) ? $arguments['type'] : null;
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        if (preg_match(Regex::getFrom(), $data, $matches)) {
            $result = Arrays::arraySafe([
                'type' => is_null($type) ? Table::METADATA() : $type,
                'value' => trim($data),
                'table' => $matches['table_name'],
                'alias' => $matches['table_alias'] ?? null,
            ]);
        }
        return $result;
    }

    /**
     * Parse JOIN criteria (not supported for INI, returns empty array).
     *
     * @param array $arguments The arguments.
     * @return array
     */
    public static function getJoin(array $arguments): array
    {
        $result = [];
        if (Arrays::isMultidimensional($arguments['data'])) {
            $tableSets = call_user_func_array('array_merge', $arguments['data']);
            foreach ($tableSets as $table) {
                if (!str_contains($table, '=')) {
                    $result['join'] = self::getFrom(['type' => $arguments['type'], 'data' => $table]);
                }
                if (str_contains($table, '=')) {
                    $result['on'] = self::getOn(['junction' => $arguments['junction'], 'data' => $table]);
                }
            }
        } else {
            if (is_array($arguments['data'])) {
                foreach ($arguments['data'] as $table) {
                    $result = self::getFrom(['type' => $arguments['type'], 'data' => $table]);
                }
            } else {
                $result = self::getFrom(['type' => $arguments['type'], 'data' => $arguments['data']]);
            }
        }
        return $result;
    }

    /**
     * Parse ON criteria (not supported for INI, returns empty array).
     *
     * @param array $arguments The arguments.
     * @return array
     */
    public static function getOn(array $arguments): array
    {
        $result = [];
        $junction = array_key_exists('junction', $arguments) ? $arguments['junction'] : Junction::NONE();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        if (preg_match(Regex::getOn(), $data, $matches)) {
            $result = Arrays::arraySafe([
                'junction' => $junction,
                'value' => trim($data),
                'host' => [
                    'table' => $matches['table_prefix_host'],
                    'column' => $matches['column_name_host'] ?? null,
                ],
                'signal' => $matches['signal'],
                'consumer' => [
                    'table' => $matches['table_prefix_consumer'],
                    'column' => $matches['column_name_consumer'] ?? null,
                ],
            ]);
        }
        return $result;
    }

    /**
     * Parse WHERE/HAVING criteria.
     *
     * @param array $arguments The arguments.
     * @return array
     */
    public static function getWhereHaving(array $arguments): array
    {
        $result = [];
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $enum = array_key_exists('enum', $arguments) ? $arguments['enum'] : Where::class;
        $condition = array_key_exists('condition', $arguments) ? $arguments['condition'] : Condition::NONE();

        if (preg_match(Regex::getWhereHaving(), $data, $matches)) {
            $aggregationType = match (true) {
                str_contains($data, 'IN') => $enum::IN(),
                str_contains($data, 'LIKE') => $enum::LIKE(),
                str_contains($data, 'BETWEEN') => $enum::BETWEEN(),
                default => $enum::NONE()
            };

            $aggregationAssert = match (true) {
                str_contains($data, 'NOT') => $enum::NEGATION(),
                default => $enum::AFFIRMATION()
            };

            // For LIKE, extract full pattern after "LIKE " (regex getWhereHaving may cut at space)
            if ($aggregationType === $enum::LIKE() && preg_match('/\s+LIKE\s+(.+)$/is', $data, $likeMatch)) {
                $matches['arguments'] = trim(trim($likeMatch[1]), "'\"");
                $matches['arguments_unlimited'] = $matches['arguments'];
            }

            $result = isset($matches['function_name']) ? Arrays::arraySafe([
                'type' => $enum::FUNCTION(),
                'value' => trim($data),
                'function' => $matches['function_name'],
                'alias' => $matches['function_table_alias'] ?? null,
                'column' => $matches['function_column_name'] ?? null,
                'arguments' => [
                    'default' => $matches['function_arguments'] ?? null,
                    'extra' => $matches['function_arguments_extra'] ?? null,
                    'unlimited' => $matches['function_arguments_unlimited'] ?? null,
                ],
                'aggregation' => [
                    'value' => $matches['function_aggregation'] ?? null,
                    'type' => $aggregationType,
                    'assert' => $aggregationAssert,
                ],
                'signal' => $matches['function_signal'] ?? null,
                'condition' => $condition,
            ]) : Arrays::arraySafe([
                'type' => $enum::DEFAULT(),
                'value' => trim($data),
                'alias' => $matches['table_alias'] ?? null,
                'column' => $matches['column_name'] ?? null,
                'arguments' => [
                    'default' => $matches['arguments'] ?? null,
                    'extra' => $matches['arguments_extra'] ?? null,
                    'unlimited' => $matches['arguments_unlimited'] ?? null,
                ],
                'aggregation' => [
                    'value' => $matches['aggregation'] ?? null,
                    'type' => $aggregationType,
                    'assert' => $aggregationAssert,
                ],
                'signal' => $matches['signal'] ?? null,
                'condition' => $condition,
            ]);
        }
        return $result;
    }

    /**
     * Parse GROUP BY criteria.
     *
     * @param array $arguments The arguments.
     * @return array
     */
    public static function getGroup(array $arguments): array
    {
        $result = [];
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        if (preg_match(Regex::getGroupOrder(), $data, $matches)) {
            if (isset($matches['function_name'])) {
                $result = Arrays::arraySafe([
                    'type' => Grouping::FUNCTION(),
                    'value' => trim($data),
                    'function' => $matches['function_name'],
                    'arguments' => $matches['function_arguments'],
                ]);
                unset($result['prefix']);
            } else {
                $result = Arrays::arraySafe([
                    'type' => Grouping::METADATA(),
                    'value' => trim($data),
                    'prefix' => $matches['table_prefix'] ?? null,
                    'column' => $matches['column_name'],
                ]);
            }
        }
        return $result;
    }

    /**
     * Parse ORDER BY criteria.
     *
     * @param array $arguments The arguments.
     * @return array
     */
    public static function getOrder(array $arguments): array
    {
        $result = [];
        $sorting = array_key_exists('sorting', $arguments) ? $arguments['sorting'] : Sorting::NONE();
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        $data = trim($data);
        if (preg_match('/^(.+?)\s+(ASC|DESC)\s*$/i', $data, $dirMatch)) {
            $data = trim($dirMatch[1]);
            $sorting = strtoupper($dirMatch[2]) === 'DESC' ? Sorting::DESCENDING() : Sorting::ASCENDING();
        }
        if (preg_match(Regex::getGroupOrder(), $data, $matches)) {
            if (!empty($matches['function_name'])) {
                $result = Arrays::arraySafe([
                    'type' => Sorting::FUNCTION(),
                    'value' => $data,
                    'function' => $matches['function_name'],
                    'arguments' => $matches['function_arguments'],
                    'sorting' => $sorting
                ]);
                unset($result['prefix']);
            } else {
                $result = Arrays::arraySafe([
                    'type' => Sorting::METADATA(),
                    'value' => $data,
                    'prefix' => $matches['table_prefix'] ?? null,
                    'column' => $matches['column_name'],
                    'sorting' => $sorting
                ]);
            }
        }
        return $result;
    }

    /**
     * Parse LIMIT criteria.
     *
     * @param array $arguments The arguments.
     * @return array
     */
    public static function getLimit(array $arguments): array
    {
        $result = [];
        $data = array_key_exists('data', $arguments) ? $arguments['data'] : [];
        if (preg_match(Regex::getLimit(), $data, $matches)) {
            $result = Arrays::arraySafe([
                'type' => isset($matches['offset']) ? Limit::OFFSET() : Limit::DEFAULT(),
                'value' => trim($data),
                'limit' => (int) $matches['limit'],
                'offset' => isset($matches['offset']) ? (int) $matches['offset'] : null,
            ]);
        }
        return $result;
    }
}
