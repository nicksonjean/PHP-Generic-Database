<?php

namespace GenericDatabase\Engine\ODBC\Connection\Report;

use Exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\ODBC\Connection\ODBC;
use GenericDatabase\Engine\ODBC\Connection\Statements\StatementsHandler;

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
        $state = odbc_error($this->getInstance()->getConnection());
        $error = odbc_errormsg($this->getInstance()->getConnection());
        if ($state !== '00000' && !empty($error)) {
            if ($this->getReportMode() & ODBC::REPORT_STRICT) {
                throw new Exception("[$state] $error");
            } elseif ($this->getReportMode() & ODBC::REPORT_ERROR) {
                trigger_error("[$state] $error", E_USER_WARNING);
            }
        } elseif ($message && $this->getReportMode() & ODBC::REPORT_STRICT) {
            throw new Exception($message);
        }
    }
}
