<?php

namespace GenericDatabase\Engine\MySQLi;

enum CharsetType
{
  case Client;
  case Results;
  case Connection;

  public function getCharsetType(): string
  {
    return match ($this) {
      CharsetType::Client => 'character_set_client',
      CharsetType::Results => 'character_set_results',
      CharsetType::Connection => 'character_set_connection',
    };
  }

  public function getInverseCharsetType(): string
  {
    return match ($this) {
      CharsetType::Client => 'client',
      CharsetType::Results => 'results',
      CharsetType::Connection => 'connection',
    };
  }
}
