<?php

namespace GenericDatabase\Generic\QueryBuilder;

/**
 * This trait is part of the Generic QueryBuilder module and is used to encapsulate
 * reusable query-building logic for database operations.
 *
 * @package Generic\QueryBuilder
 */
trait Query
{
    /**
     * @var QueryObject The query object instance used to build and execute database queries.
     */
    public QueryObject $query;

    /**
     * Initializes the query object, this method is used to lazily set the query object if it has not been set.
     *
     * @return void
     */
    public function initQuery(): void
    {
        if (!isset($this->query)) {
            $this->query = new QueryObject();
        }
    }
}

