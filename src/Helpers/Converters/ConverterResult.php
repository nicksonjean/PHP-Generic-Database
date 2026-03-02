<?php

namespace GenericDatabase\Helpers\Converters;

/**
 * Holds the outcome of converting one source directory to one target format directory.
 *
 * Files can be in four states after a conversion pass:
 *   - updated   : file was written (new or changed content)
 *   - unchanged : file already had the correct content — not touched
 *   - skipped   : target subdirectory does not exist — file intentionally bypassed
 *   - error     : write operation failed
 *
 * getSummary() returns a concise human-readable line suitable for CLI output or logs.
 */
class ConverterResult
{
    private string $sourceFormat;
    private string $targetFormat;
    private string $targetDir;

    /** @var list<string> Relative paths of files that were written */
    private array $updated = [];

    /** @var list<string> Relative paths of files that were not touched (same content) */
    private array $unchanged = [];

    /** @var array<string, string> relPath => skip reason */
    private array $skipped = [];

    /** @var array<string, string> relPath => error message */
    private array $errors = [];

    public function __construct(string $sourceFormat, string $targetFormat, string $targetDir)
    {
        $this->sourceFormat = $sourceFormat;
        $this->targetFormat = $targetFormat;
        $this->targetDir    = $targetDir;
    }

    public function addUpdated(string $relPath): void
    {
        $this->updated[] = $relPath;
    }

    public function addUnchanged(string $relPath): void
    {
        $this->unchanged[] = $relPath;
    }

    public function addSkipped(string $relPath, string $reason): void
    {
        $this->skipped[$relPath] = $reason;
    }

    public function addError(string $relPath, string $message): void
    {
        $this->errors[$relPath] = $message;
    }

    public function getSourceFormat(): string
    {
        return $this->sourceFormat;
    }

    public function getTargetFormat(): string
    {
        return $this->targetFormat;
    }

    public function getTargetDir(): string
    {
        return $this->targetDir;
    }

    /** @return list<string> */
    public function getUpdated(): array
    {
        return $this->updated;
    }

    /** @return list<string> */
    public function getUnchanged(): array
    {
        return $this->unchanged;
    }

    /** @return array<string, string> */
    public function getSkipped(): array
    {
        return $this->skipped;
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getTotalUpdated(): int
    {
        return count($this->updated);
    }

    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    /**
     * Return a human-readable summary line.
     * Example: "json -> csv | /path/to/csv | 40 updated | 2 unchanged | 0 skipped | OK"
     */
    public function getSummary(): string
    {
        $status = empty($this->errors)
            ? 'OK'
            : sprintf('%d error(s)', count($this->errors));

        return sprintf(
            '%s -> %s | %s | %d updated | %d unchanged | %d skipped | %s',
            $this->sourceFormat,
            $this->targetFormat,
            $this->targetDir,
            count($this->updated),
            count($this->unchanged),
            count($this->skipped),
            $status
        );
    }
}
