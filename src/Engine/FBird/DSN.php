<?php

namespace GenericDatabase\Engine\FBird;

use GenericDatabase\Traits\Path;
use GenericDatabase\Engine\FBirdEngine;

class DSN
{
    public static function parseDsn(): string|\Exception
    {
        if (!extension_loaded('interbase')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'interbase',
                'PHP.ini'
            );
            throw new \Exception($message);
        }

        if (!Path::isAbsolute(FBirdEngine::getInstance()->getDatabase())) {
            FBirdEngine::getInstance()->setDatabase(Path::toAbsolute(FBirdEngine::getInstance()->getDatabase()));
        }

        $result = null;
        $result = sprintf(
            "ibase://%s:%s@%s:%s//%s?charset=%s",
            FBirdEngine::getInstance()->getUser(),
            FBirdEngine::getInstance()->getPassword(),
            FBirdEngine::getInstance()->getHost(),
            FBirdEngine::getInstance()->getPort(),
            FBirdEngine::getInstance()->getDatabase(),
            FBirdEngine::getInstance()->getCharset()
        );

        FBirdEngine::getInstance()->setDsn((string) $result);
        return $result;
    }
}
