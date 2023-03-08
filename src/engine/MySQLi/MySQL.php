<?php

namespace GenericDatabase\Engine\MySQLi;

enum MySQL
{
  case ATTR_OPT_CONNECT_TIMEOUT;
  case ATTR_OPT_READ_TIMEOUT;
  case ATTR_OPT_LOCAL_INFILE;
  case ATTR_INIT_COMMAND;
  case ATTR_SET_CHARSET_NAME;
  case ATTR_READ_DEFAULT_FILE;
  case ATTR_READ_DEFAULT_GROUP;
  case ATTR_SERVER_PUBLIC_KEY;
  case ATTR_OPT_NET_CMD_BUFFER_SIZE;
  case ATTR_OPT_NET_READ_BUFFER_SIZE;
  case ATTR_OPT_INT_AND_FLOAT_NATIVE;
  case ATTR_OPT_SSL_VERIFY_SERVER_CERT;
  case ATTR_PERSISTENT;

  public function value($value)
  {
    return match ($this) {
      self::ATTR_OPT_CONNECT_TIMEOUT => [$this->name => $value],
      self::ATTR_OPT_READ_TIMEOUT => [$this->name => $value],
      self::ATTR_OPT_LOCAL_INFILE => [$this->name => $value],
      self::ATTR_INIT_COMMAND => [$this->name => $value],
      self::ATTR_SET_CHARSET_NAME => [$this->name => $value],
      self::ATTR_READ_DEFAULT_FILE => [$this->name => $value],
      self::ATTR_READ_DEFAULT_GROUP => [$this->name => $value],
      self::ATTR_SERVER_PUBLIC_KEY => [$this->name => $value],
      self::ATTR_OPT_NET_CMD_BUFFER_SIZE => [$this->name => $value],
      self::ATTR_OPT_NET_READ_BUFFER_SIZE => [$this->name => $value],
      self::ATTR_OPT_INT_AND_FLOAT_NATIVE => [$this->name => $value],
      self::ATTR_OPT_SSL_VERIFY_SERVER_CERT => [$this->name => $value],
      self::ATTR_PERSISTENT => [$this->name => $value],
    };
  }
}
