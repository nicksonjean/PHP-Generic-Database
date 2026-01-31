<?php

namespace GenericDatabase\Engine\CSV\Connection\Report;

use Exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\CSV\Connection\CSV;

class ReportHandler implements IReport
{
    protected static mixed $errorMode;

    private ?IConnection $connection = null;

    public function __construct(?IConnection $connection = null)
    {
        $this->connection = $connection;
    }

    public function getInstance(): IConnection
    {
        return $this->connection;
    }

    public function getReportMode(): mixed
    {
        return self::$errorMode;
    }

    public function setReportMode($mode): void
    {
        self::$errorMode = $mode;
        $conn = $this->getInstance()->getConnection();
        if (is_object($conn) && method_exists($conn, 'enableExceptions')) {
            $conn->enableExceptions(($mode & CSV::REPORT_STRICT) !== 0);
        }
    }

    public function handleError($message = null): void
    {
        $conn = $this->getInstance()->getConnection();
        $errorCode = is_object($conn) && method_exists($conn, 'lastErrorCode') ? $conn->lastErrorCode() : 0;
        $errorMsg = is_object($conn) && method_exists($conn, 'lastErrorMsg') ? $conn->lastErrorMsg() : '';
        if ($errorCode !== 0) {
            if ($this->getReportMode() & CSV::REPORT_STRICT) {
                throw new Exception($errorMsg, $errorCode);
            } elseif ($this->getReportMode() & CSV::REPORT_ERROR) {
                trigger_error($errorMsg, E_USER_WARNING);
            }
        } elseif ($message && $this->getReportMode() & CSV::REPORT_STRICT) {
            throw new Exception($message);
        }
    }
}
