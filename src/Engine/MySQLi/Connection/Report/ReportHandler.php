<?php

namespace GenericDatabase\Engine\MySQLi\Connection\Report;

use mysqli_sql_exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\MySQLi\Connection\MySQL;
use GenericDatabase\Engine\MySQLi\Connection\Statements\StatementsHandler;

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
        mysqli_report($mode);
    }

    public function handleError($message = null): void
    {
        if ($this->getReportMode() & MySQL::REPORT_STRICT) {
            $error = mysqli_error($this->getInstance()->getConnection());
            if (!empty($error)) {
                throw new mysqli_sql_exception($error, mysqli_errno($this->getInstance()->getConnection()));
            } elseif ($message) {
                throw new mysqli_sql_exception($message);
            }
        } elseif ($this->getReportMode() & MySQL::REPORT_ERROR) {
            $error = mysqli_error($this->getInstance()->getConnection());
            if (!empty($error)) {
                trigger_error($error, E_USER_WARNING);
            }
        }
    }
}
