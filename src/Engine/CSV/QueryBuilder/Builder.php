<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\CSV\QueryBuilder;

use GenericDatabase\Engine\JSON\QueryBuilder\Builder as JSONBuilder;

/**
 * Builder class for CSV QueryBuilder.
 * Reuses JSON builder logic since CSV data is processed the same way.
 *
 * @package GenericDatabase\Engine\CSV\QueryBuilder
 */
class Builder extends JSONBuilder
{
}

