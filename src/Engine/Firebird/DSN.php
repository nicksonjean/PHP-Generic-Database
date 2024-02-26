<?php

namespace GenericDatabase\Engine\Firebird;

use AllowDynamicProperties;
use GenericDatabase\Engine\FirebirdEngine;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parseDsn(): string|CustomException
    {
        if (!extension_loaded('interbase')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'interbase',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        if (!Path::isAbsolute(FirebirdEngine::getInstance()->getDatabase())) {
            FirebirdEngine::getInstance()->setDatabase(Path::toAbsolute(
                FirebirdEngine::getInstance()->getDatabase()
            ));
        }

        $result = sprintf(
            "ibase://%s:%s@%s:%s//%s?charset=%s",
            FirebirdEngine::getInstance()->getUser(),
            FirebirdEngine::getInstance()->getPassword(),
            FirebirdEngine::getInstance()->getHost(),
            FirebirdEngine::getInstance()->getPort(),
            FirebirdEngine::getInstance()->getDatabase(),
            FirebirdEngine::getInstance()->getCharset()
        );

        FirebirdEngine::getInstance()->setDsn($result);
        return $result;
    }
}
