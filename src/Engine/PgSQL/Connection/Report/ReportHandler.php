<?php

namespace GenericDatabase\Engine\PgSQL\Connection\Report;

use Exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;
use GenericDatabase\Engine\PgSQL\Connection\Statements\StatementsHandler;

class ReportHandler implements IReport
{
    protected static mixed $errorMode;

    public function getInstance(): IConnection
    {
        return StatementsHandler::getInstance();
    }

    public function getReportMode(): mixed
    {
        return self::$errorMode;
    }

    public function setReportMode($mode): void
    {
        self::$errorMode = $mode;
    }

    public function handleError($message = null): void
    {
        $error = pg_last_error($this->getInstance()->getConnection());
        if (!empty($error)) {
            if ($this->getReportMode() & PgSQL::REPORT_STRICT) {
                throw new Exception($error);
            } elseif ($this->getReportMode() & PgSQL::REPORT_ERROR) {
                trigger_error($error, E_USER_WARNING);
            }
        } elseif ($message && $this->getReportMode() & PgSQL::REPORT_STRICT) {
            throw new Exception($message);
        }
    }
}
