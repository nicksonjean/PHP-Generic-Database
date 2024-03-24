<?php

namespace GenericDatabase\Engine\MySQLi;

use AllowDynamicProperties;
use GenericDatabase\Engine\MySQLiEngine;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
    {
        if (!extension_loaded('mysqli')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'mysqli',
                'PHP.ini'
            );
            throw new CustomException($message);
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
