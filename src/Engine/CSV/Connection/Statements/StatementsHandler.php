<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\CSV\Connection\Statements;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Interfaces\Connection\IFlatFileStatements;
use GenericDatabase\Abstract\AbstractFlatFileStatements;
use GenericDatabase\Engine\CSV\Connection\Options\OptionsHandler;
use GenericDatabase\Engine\CSV\Connection\Report\ReportHandler;

/**
 * Handles SQL-like statement operations for CSV connections.
 * Extends AbstractFlatFileStatements to leverage common flat-file functionality.
 *
 * @package GenericDatabase\Engine\CSV\Connection\Statements
 */
class StatementsHandler extends AbstractFlatFileStatements implements IFlatFileStatements
{
    /**
     * @var IConnection The connection instance.
     */
    private IConnection $connection;

    /**
     * Constructor.
     *
     * @param IConnection $connection The connection instance.
     * @param IOptions|null $optionsHandler Options handler instance (optional).
     * @param IReport|null $reportHandler Report handler instance (optional).
     */
    public function __construct(
        IConnection $connection,
        ?IOptions $optionsHandler = null,
        ?IReport $reportHandler = null
    ) {
        $this->connection = $connection;

        // Create default handlers if not provided
        $optionsHandler = $optionsHandler ?? new OptionsHandler($connection);
        $reportHandler = $reportHandler ?? new ReportHandler();

        parent::__construct($connection, $optionsHandler, $reportHandler);
    }

    /**
     * Execute a query.
     *
     * @param mixed ...$params The parameters.
     * @return IConnection|null
     */
    public function query(mixed ...$params): ?IConnection
    {
        $query = $params[0] ?? '';
        $this->setAllMetadata();
        $this->setQueryString($query);
        $this->setStatement($query);
        return $this->connection;
    }

    /**
     * Prepare a statement.
     *
     * @param mixed ...$params The parameters.
     * @return IConnection|null
     */
    public function prepare(mixed ...$params): ?IConnection
    {
        $query = $params[0] ?? '';
        $this->setAllMetadata();
        $this->setQueryString($query);
        $this->setStatement($query);
        return $this->connection;
    }

    /**
     * Execute a statement.
     *
     * @param mixed ...$params The parameters.
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        if (isset($params[0]) && is_array($params[0])) {
            $this->setQueryParameters($params[0]);
        }
        return $this->getAffectedRows();
    }
}
