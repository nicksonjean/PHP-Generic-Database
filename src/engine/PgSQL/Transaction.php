<?php

namespace GenericDatabase\Engine\PgSQL;

use GenericDatabase\Engine\PgSQLEngine;

class Transaction
{
  protected $transactionCounter = 0;

  public function beginTransaction()
  {
    if (!$this->transactionCounter++) {
      return PgSQLEngine::getInstance()?->beginTransaction();
    }
    PgSQLEngine::getInstance()?->exec('SAVEPOINT trans' . ($this->transactionCounter));
    return $this->transactionCounter >= 0;
  }

  public function commit()
  {
    if (!--$this->transactionCounter) {
      return PgSQLEngine::getInstance()?->commit();
    }
    return $this->transactionCounter >= 0;
  }

  public function rollback()
  {
    if (--$this->transactionCounter) {
      PgSQLEngine::getInstance()?->exec('ROLLBACK TO trans' . ($this->transactionCounter + 1));
      return true;
    }
    return PgSQLEngine::getInstance()?->rollback();
  }
}
