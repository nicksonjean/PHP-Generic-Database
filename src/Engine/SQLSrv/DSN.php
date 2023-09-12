<?php

namespace GenericDatabase\Engine\SQLSrv;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parseDsn(): string|CustomException
    {
        if (!extension_loaded('sqlsrv')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'sqlsrv',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        $result = sprintf(
            "sqlsrv://%s:%s@%s:%s/?database=%s&charset=%s%s",
            SQLSrvEngine::getInstance()->getUser(),
            SQLSrvEngine::getInstance()->getPassword(),
            SQLSrvEngine::getInstance()->getHost(),
            SQLSrvEngine::getInstance()->getPort(),
            SQLSrvEngine::getInstance()->getDatabase(),
            SQLSrvEngine::getInstance()->getCharset(),
            Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                ? '&timeout=' . Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                : '',
        );

        SQLSrvEngine::getInstance()->setDsn($result);
        return $result;
    }
}
