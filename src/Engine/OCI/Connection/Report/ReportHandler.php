<?php

namespace GenericDatabase\Engine\OCI\Connection\Report;

use Exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\OCI\Connection\OCI;
use GenericDatabase\Engine\OCI\Connection\Statements\StatementsHandler;

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
        $error = oci_error($this->getInstance()->getConnection());
        if ($error !== false) {
            if ($this->getReportMode() & OCI::REPORT_STRICT) {
                throw new Exception($error['message'], $error['code']);
            } elseif ($this->getReportMode() & OCI::REPORT_ERROR) {
                trigger_error($error['message'], E_USER_WARNING);
            }
        } elseif ($message && $this->getReportMode() & OCI::REPORT_STRICT) {
            throw new Exception($message);
        }
    }
}
