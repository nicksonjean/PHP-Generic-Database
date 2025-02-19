<?php

namespace GenericDatabase\Generic\Statements;

class Metadata
{
    public QueryMetadata $query;

    public function __construct()
    {
        $this->query = new QueryMetadata();
    }

    public function getQuery(): QueryMetadata
    {
        return $this->query;
    }
}
