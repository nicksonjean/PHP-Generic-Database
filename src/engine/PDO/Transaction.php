<?php

namespace GenericDatabase\Engine\PDO;

use GenericDatabase\Engine\PDOEngine;

class Transaction
{
  protected $transactionCounter = 0;

  public function beginTransaction()
  {
    if (!$this->transactionCounter++) {
      return PDOEngine::getInstance()?->beginTransaction();
    }
    PDOEngine::getInstance()?->exec('SAVEPOINT trans' . ($this->transactionCounter));
    return $this->transactionCounter >= 0;
  }

  public function commit()
  {
    if (!--$this->transactionCounter) {
      return PDOEngine::getInstance()?->commit();
    }
    return $this->transactionCounter >= 0;
  }

  public function rollback()
  {
    if (--$this->transactionCounter) {
      PDOEngine::getInstance()?->exec('ROLLBACK TO trans' . ($this->transactionCounter + 1));
      return true;
    }
    return PDOEngine::getInstance()?->rollback();
  }
}
