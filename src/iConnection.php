<?php

namespace GenericDatabase;

interface iConnection
{
  public function connect(): iConnection;
  public function getConnection();
  public function setConnection($connection): mixed;
  public function loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null);
  public function beginTransaction();
  public function commit();
  public function rollback();
  public function inTransaction();
  public function lastInsertId(?string $name = null);
  public function quote(mixed ...$params): mixed;
  public function prepare(mixed ...$params): mixed;
  public function query(mixed ...$params): mixed;
  public function exec(mixed ...$params): mixed;
  public function getAttribute(int $attribute);
  public function setAttribute(int $attribute, string $value);
  public function errorCode(?int $inst = null): mixed;
  public function errorInfo(?int $inst = null): mixed;
}
