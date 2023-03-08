<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Engine\MySQLiEngine;

class Transaction
{
  protected $transactionCounter = 0;

  public function beginTransaction()
  {
    if (!$this->transactionCounter++) {
      return MySQLiEngine::getInstance()?->beginTransaction();
    }
    MySQLiEngine::getInstance()?->exec('SAVEPOINT trans' . ($this->transactionCounter));
    return $this->transactionCounter >= 0;
  }

  public function commit()
  {
    if (!--$this->transactionCounter) {
      return MySQLiEngine::getInstance()?->commit();
    }
    return $this->transactionCounter >= 0;
  }

  public function rollback()
  {
    if (--$this->transactionCounter) {
      MySQLiEngine::getInstance()?->exec('ROLLBACK TO trans' . ($this->transactionCounter + 1));
      return true;
    }
    return MySQLiEngine::getInstance()?->rollback();
  }
}
