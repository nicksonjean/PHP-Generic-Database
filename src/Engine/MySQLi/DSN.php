<?php

namespace GenericDatabase\Engine\MySQLi;

use AllowDynamicProperties;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Helpers\GenericException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws GenericException
     */
    public static function parseDsn(): string|GenericException
    {
        if (!extension_loaded('mysqli')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'mysqli',
                'PHP.ini'
            );
            throw new GenericException($message);
        }

        $result = sprintf(
            "mysql://%s:%s@%s:%s/%s?charset=%s",
            MySQLiEngine::getInstance()->getUser(),
            MySQLiEngine::getInstance()->getPassword(),
            MySQLiEngine::getInstance()->getHost(),
            MySQLiEngine::getInstance()->getPort(),
            MySQLiEngine::getInstance()->getDatabase(),
            MySQLiEngine::getInstance()->getCharset()
        );

        MySQLiEngine::getInstance()->setDsn($result);
        return $result;
    }
}
