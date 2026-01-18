<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\Report;

use Exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;
use GenericDatabase\Engine\SQLSrv\Connection\Statements\StatementsHandler;

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
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
        if ($errors !== null) {
            $errorMsg = "";
            $errorCode = 0;
            foreach ($errors as $error) {
                $errorMsg .= "[" . $error['code'] . "] " . $error['message'] . "\n";
                if ($errorCode === 0) {
                    $errorCode = $error['code'];
                }
            }
            if ($this->getReportMode() & SQLSrv::REPORT_STRICT) {
                throw new Exception($errorMsg, $errorCode);
            } elseif ($this->getReportMode() & SQLSrv::REPORT_ERROR) {
                trigger_error($errorMsg, E_USER_WARNING);
            }
        } elseif ($message && $this->getReportMode() & SQLSrv::REPORT_STRICT) {
            throw new Exception($message);
        }
    }
}

