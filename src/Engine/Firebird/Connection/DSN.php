<?php

namespace GenericDatabase\Engine\Firebird\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\FirebirdConnection;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
    {
        if (!extension_loaded('interbase')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'interbase',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        if (!Path::isAbsolute(FirebirdConnection::getInstance()->getDatabase())) {
            FirebirdConnection::getInstance()->setDatabase(Path::toAbsolute(
                FirebirdConnection::getInstance()->getDatabase()
            ));
        }

        $result = sprintf(
            "ibase://%s:%s@%s:%s//%s?charset=%s",
            FirebirdConnection::getInstance()->getUser(),
            FirebirdConnection::getInstance()->getPassword(),
            FirebirdConnection::getInstance()->getHost(),
            FirebirdConnection::getInstance()->getPort(),
            FirebirdConnection::getInstance()->getDatabase(),
            FirebirdConnection::getInstance()->getCharset()
        );

        FirebirdConnection::getInstance()->setDsn($result);
        return $result;
    }
}
