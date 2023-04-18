<?php

namespace GenericDatabase\Engine\FBird;

use
  GenericDatabase\Traits\Path,
  GenericDatabase\Engine\FBirdEngine;

class DSN
{
  public static function parseDsn(): string|\Exception
  {
    if (!extension_loaded('interbase')) {
      $message = sprintf(
        "Invalid or not loaded '%s' extension in '%s' settings",
        ['interbase', 'PHP.ini']
      );
      throw new \Exception($message);
    }
    $result = null;
    $result = self::DSNFBird();
    FBirdEngine::getInstance()->setDsn((string) $result);
    return $result;
  }

  private static function DSNFBird(): string
  {
    if (!Path::isAbsolute(FBirdEngine::getInstance()->getDatabase())) {
      FBirdEngine::getInstance()->setDatabase(Path::toAbsolute(FBirdEngine::getInstance()->getDatabase()));
    }

    return sprintf(
      "firebase:%s:%s@%s:%s//%s?charset=%s",
      FBirdEngine::getInstance()->getUser(),
      FBirdEngine::getInstance()->getPassword(),
      FBirdEngine::getInstance()->getHost(),
      FBirdEngine::getInstance()->getPort(),
      FBirdEngine::getInstance()->getDatabase(),
      FBirdEngine::getInstance()->getCharset()
    );
  }
}
