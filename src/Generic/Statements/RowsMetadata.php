<?php

namespace GenericDatabase\Generic\Statements;

class RowsMetadata
{
    public int $fetched = 0;
    public int $affected = 0;

    public function getFetched(): int
    {
        return $this->fetched;
    }

    public function setFetched(int $fetched): self
    {
        $this->fetched = $fetched;
        return $this;
    }

    public function getAffected(): int
    {
        return $this->affected;
    }

    public function setAffected(int $affected): self
    {
        $this->affected = $affected;
        return $this;
    }
}
