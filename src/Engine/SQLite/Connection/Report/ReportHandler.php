<?php

namespace GenericDatabase\Engine\SQLite\Connection\Report;

use Exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\SQLite\Connection\SQLite;
use GenericDatabase\Engine\SQLite\Connection\Statements\StatementsHandler;

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
        if ($mode & SQLite::REPORT_STRICT) {
            $this->getInstance()->getConnection()->enableExceptions(true);
        } else {
            $this->getInstance()->getConnection()->enableExceptions(false);
        }
    }

    public function handleError($message = null): void
    {
        $errorCode = $this->getInstance()->getConnection()->lastErrorCode();
        $errorMsg = $this->getInstance()->getConnection()->lastErrorMsg();
        if ($errorCode !== 0) {
            if ($this->getReportMode() & SQLite::REPORT_STRICT) {
                throw new Exception($errorMsg, $errorCode);
            } elseif ($this->getReportMode() & SQLite::REPORT_ERROR) {
                trigger_error($errorMsg, E_USER_WARNING);
            }
        } elseif ($message && $this->getReportMode() & SQLite::REPORT_STRICT) {
            throw new Exception($message);
        }
    }
}
