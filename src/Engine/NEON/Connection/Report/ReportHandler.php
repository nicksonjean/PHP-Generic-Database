<?php

namespace GenericDatabase\Engine\NEON\Connection\Report;

use Exception;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\NEON\Connection\NEON;

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
        if ($mode & NEON::REPORT_STRICT) {
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
            if ($this->getReportMode() & NEON::REPORT_STRICT) {
                throw new Exception($errorMsg, $errorCode);
            } elseif ($this->getReportMode() & NEON::REPORT_ERROR) {
                trigger_error($errorMsg, E_USER_WARNING);
            }
        } elseif ($message && $this->getReportMode() & NEON::REPORT_STRICT) {
            throw new Exception($message);
        }
    }
}
