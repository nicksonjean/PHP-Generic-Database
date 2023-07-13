<?php

namespace GenericDatabase\Engine\FBird;

use AllowDynamicProperties;
use GenericDatabase\Engine\FBirdEngine;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\GenericException;

#[AllowDynamicProperties]
class DSN
{
    public static function parseDsn(): string|GenericException
    {
        if (!extension_loaded('interbase')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'interbase',
                'PHP.ini'
            );
            throw new GenericException($message);
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
