<?php

namespace GenericDatabase\Engine\PDO\Connection\Report;

use PDO;
use PDOException;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Engine\PDO\Connection\XPDO;
use GenericDatabase\Engine\PDO\Connection\Statements\StatementsHandler;

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
        if ($mode & XPDO::REPORT_STRICT) {
            $this->getInstance()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } elseif ($mode & XPDO::REPORT_ERROR) {
            $this->getInstance()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        } else {
            $this->getInstance()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        }
    }

    public function handleError($message = null): void
    {
        if ($this->getReportMode() & XPDO::REPORT_STRICT) {
            $errorInfo = $this->getInstance()->getConnection()->errorInfo();
            if ($errorInfo[0] !== '00000') {
                throw new PDOException($errorInfo[2], (int)$errorInfo[1]);
            } elseif ($message) {
                throw new PDOException($message);
            }
        } elseif ($this->getReportMode() & XPDO::REPORT_ERROR) {
            $errorInfo = $this->getInstance()->getConnection()->errorInfo();
            if ($errorInfo[0] !== '00000') {
                trigger_error($errorInfo[2], E_USER_WARNING);
            }
        }
    }
}

