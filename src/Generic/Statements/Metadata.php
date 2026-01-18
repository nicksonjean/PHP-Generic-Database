<?php

namespace GenericDatabase\Generic\Statements;

/**
 * The Metadata class represents metadata associated with a query, and it contains a single property $query of type QueryMetadata.
 *
 * Methods:
 * - `__construct()`: Initializes a new Metadata object by creating a new QueryMetadata object and assigning it to the $query property.
 * - `getQuery(): QueryMetadata`: Retrieves the QueryMetadata object associated with this metadata, returning it as a QueryMetadata object.
 *
 * Fields:
 * - `$query`: Stores QuertMetadata for dynamic property access.
 */
class Metadata
{
    /**
     * @var QueryMetadata $query The QueryMetadata object associated with this metadata.
     */
    public QueryMetadata $query;

    /**
     * Metadata constructor.
     * Initializes a new QueryMetadata object.
     */
    public function __construct()
    {
        $this->query = new QueryMetadata();
    }

    /**
     * Retrieves the QueryMetadata object.
     *
     * @return QueryMetadata The QueryMetadata object.
     */
    public function getQuery(): QueryMetadata
    {
        return $this->query;
    }
}

