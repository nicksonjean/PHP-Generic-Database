<?php

namespace GenericDatabase\Generic\Statements;

class QueryMetadata
{
    public string $string = '';
    public ?array $arguments = [];
    public int $columns = 0;
    public RowsMetadata $rows;

    public function __construct()
    {
        $this->rows = new RowsMetadata();
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function setString(string $string): self
    {
        $this->string = $string;
        return $this;
    }

    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    public function setArguments(?array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    public function setColumns(int $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function getRows(): RowsMetadata
    {
        return $this->rows;
    }
}
