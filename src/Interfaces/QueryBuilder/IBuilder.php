<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\QueryBuilder;

use GenericDatabase\Helpers\Parsers\SQL\Parse;

interface IBuilder
{
    public function parse(string $query, int $quoteType = Parse::SQL_DIALECT_DOUBLE_QUOTE, ?int $quoteSkip = null): string;

    public function build(): string;

    public function buildRaw(): string;

    public function getValues(): array;
}
