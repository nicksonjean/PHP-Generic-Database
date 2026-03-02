<?php

namespace GenericDatabase\Helpers\Migrators;

/**
 * Holds the outcome of migrating one source database to one target database.
 *
 * Tables can be in three states after a migration attempt:
 *   - migrated : table created and rows inserted
 *   - skipped  : table already contained data (idempotent run)
 *   - error    : migration failed for that table
 *
 * A global error is recorded when the connection or database-creation step
 * itself fails, preventing any table from being processed.
 */
class MigrationResult
{
    private string $sourceEngine;
    private string $targetEngine;
    private string $database;

    /** @var array<string, int> table => record count */
    private array $migrated = [];

    /** @var array<string, int> table => existing record count */
    private array $skipped = [];

    /** @var array<string, string> table => error message */
    private array $errors = [];

    private ?string $globalError = null;

    public function __construct(string $sourceEngine, string $targetEngine, string $database)
    {
        $this->sourceEngine = $sourceEngine;
        $this->targetEngine = $targetEngine;
        $this->database     = $database;
    }

    public function addMigrated(string $table, int $count): void
    {
        $this->migrated[$table] = $count;
    }

    public function addSkipped(string $table, int $existingCount): void
    {
        $this->skipped[$table] = $existingCount;
    }

    public function addError(string $table, string $message): void
    {
        $this->errors[$table] = $message;
    }

    public function setGlobalError(string $message): void
    {
        $this->globalError = $message;
    }

    public function getSourceEngine(): string
    {
        return $this->sourceEngine;
    }

    public function getTargetEngine(): string
    {
        return $this->targetEngine;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    /** @return array<string, int> */
    public function getMigrated(): array
    {
        return $this->migrated;
    }

    /** @return array<string, int> */
    public function getSkipped(): array
    {
        return $this->skipped;
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getGlobalError(): ?string
    {
        return $this->globalError;
    }

    public function isSuccess(): bool
    {
        return $this->globalError === null && empty($this->errors);
    }

    public function getTotalMigratedRecords(): int
    {
        return array_sum($this->migrated);
    }

    /**
     * Return a human-readable summary line.
     * Example: "sqlite -> mysql | demodev | 8 tables | 1234 rows | OK"
     */
    public function getSummary(): string
    {
        if ($this->globalError !== null) {
            return sprintf(
                '%s -> %s | %s | FAILED: %s',
                $this->sourceEngine,
                $this->targetEngine,
                $this->database,
                $this->globalError
            );
        }

        $tableCount = count($this->migrated);
        $rowCount   = $this->getTotalMigratedRecords();
        $status     = empty($this->errors) ? 'OK' : sprintf('%d error(s)', count($this->errors));

        return sprintf(
            '%s -> %s | %s | %d table(s) | %d row(s) | %s',
            $this->sourceEngine,
            $this->targetEngine,
            $this->database,
            $tableCount,
            $rowCount,
            $status
        );
    }
}
