<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\CSV\Connection\Structure\Strategy;

use GenericDatabase\Engine\CSV\Connection\Structure\StructureHandler;
use GenericDatabase\Interfaces\Connection\IStructureStrategy;

/**
 * Strategy that delegates structure operations to StructureHandler.
 * Used by StatementsHandler and TransactionsHandler so DML and transactions
 * work without calling private methods on CSVConnection.
 * StructureHandler injects itself via setStructureHandler() after construction.
 *
 * @package GenericDatabase\Engine\CSV\Connection\Structure\Strategy
 */
class StructureStrategy implements IStructureStrategy
{
    private ?StructureHandler $structureHandler = null;

    public function __construct(?StructureHandler $structureHandler = null)
    {
        if ($structureHandler !== null) {
            $this->structureHandler = $structureHandler;
        }
    }

    /**
     * Set the structure handler (called by StructureHandler when strategy is injected).
     *
     * @param StructureHandler $handler The structure handler.
     * @return void
     */
    public function setStructureHandler(StructureHandler $handler): void
    {
        $this->structureHandler = $handler;
    }

    /**
     * @inheritDoc
     */
    public function load(?string $table = null): array
    {
        if ($this->structureHandler === null) {
            return [];
        }
        return $this->structureHandler->load($table);
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if ($this->structureHandler === null) {
            return [];
        }
        return $this->structureHandler->getData();
    }

    /**
     * @inheritDoc
     */
    public function setData(array $data): void
    {
        if ($this->structureHandler !== null) {
            $this->structureHandler->setData($data);
        }
    }

    /**
     * @inheritDoc
     */
    public function save(array $data, ?string $table = null): bool
    {
        if ($this->structureHandler === null) {
            return false;
        }
        return $this->structureHandler->save($data, $table);
    }
}
