<?php

namespace GenericDatabase\Engine\Firebird\Connection\Report;

use Exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\Firebird\Connection\Firebird;
use GenericDatabase\Engine\Firebird\Connection\Statements\StatementsHandler;

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
        if (function_exists('ibase_errcode')) {
            $errorCode = ibase_errcode();
            $errorMsg = ibase_errmsg();
            if ($errorCode !== 0) {
                if ($this->getReportMode() & Firebird::REPORT_STRICT) {
                    throw new Exception($errorMsg, $errorCode);
                } elseif ($this->getReportMode() & Firebird::REPORT_ERROR) {
                    trigger_error($errorMsg, E_USER_WARNING);
                }
            } elseif ($message && $this->getReportMode() & Firebird::REPORT_STRICT) {
                throw new Exception($message);
            }
        } else {
            $errorMsg = ibase_errmsg();
            if (!empty($errorMsg)) {
                if ($this->getReportMode() & Firebird::REPORT_STRICT) {
                    throw new Exception($errorMsg);
                } elseif ($this->getReportMode() & Firebird::REPORT_ERROR) {
                    trigger_error($errorMsg, E_USER_WARNING);
                }
            } elseif ($message && $this->getReportMode() & Firebird::REPORT_STRICT) {
                throw new Exception($message);
            }
        }
    }
}

