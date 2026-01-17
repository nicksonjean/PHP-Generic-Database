<?php

namespace GenericDatabase\Generic\Connection;

use GenericDatabase\Shared\Objectable;
use AllowDynamicProperties;

/**
 * The `GenericDatabase\Generic\Connection\Structure` class is a class that represents a structure.
 *
 * Method:
 * - `bind(array $params): object:` This method binds parameters to a structure
 *
 * @property mixed|null $structure
 * @package GenericDatabase\Helpers
 * @subpackage Structures
 */
#[AllowDynamicProperties]
class Structure
{
    use Objectable;

    /**
     * This method binds parameters to a structure
     *
     * @param array $params Arguments list
     * @return Structure
     */
    public static function bind(array $params): Structure
    {
        $result = new self();
        $result->structure = $params;
        return $result;
    }

    /**
     * Custom var_dump display
     */
    public function __debugInfo(): array
    {
        return $this->structure ?? [];
    }

    public function getTables(): ?array
    {
        return $this->structure['tables'] ?? null;
    }

    public function getSchema(): mixed
    {
        return $this->structure['schema'] ?? null;
    }

    public function getFile(): mixed
    {
        return $this->structure['file'] ?? null;
    }

    public function getData(): mixed
    {
        return $this->structure['data'] ?? null;
    }

    public function setTables(array $tables): void
    {
        if (!isset($this->structure)) {
            $this->structure = [];
        }
        $this->structure['tables'] = $tables;
    }

    public function setSchema(mixed $schema): void
    {
        if (!isset($this->structure)) {
            $this->structure = [];
        }
        $this->structure['schema'] = $schema;
    }

    public function setFile(mixed $file): void
    {
        if (!isset($this->structure)) {
            $this->structure = [];
        }
        $this->structure['file'] = $file;
    }

    public function setData(mixed $data): void
    {
        if (!isset($this->structure)) {
            $this->structure = [];
        }
        $this->structure['data'] = $data;
    }
}
