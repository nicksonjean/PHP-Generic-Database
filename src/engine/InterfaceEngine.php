<?php

namespace GenericDatabase\Engine;

interface InterfaceEngine
{
  public function connect();
  public function getConnection();
  public function setConnection($connection);
  public function loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null);
  public function getAvailableDrivers();
  public function beginTransaction();
  public function commit();
  public function rollback();
  public function inTransaction();
  public function lastInsertId(?string $name = null);
  public function quote(string $string, int $type);
  public function prepare(string $query, ?array $options = []);
  public function query(string $query, ?int $fetchMode = null);
  public function exec(string $statement);
  public function getAttribute(int $attribute);
  public function setAttribute(int $attribute, string $value);
  public function errorCode();
  public function errorInfo();
}
