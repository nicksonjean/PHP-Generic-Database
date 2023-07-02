<?php

namespace GenericDatabase\Engine\SQLSrv;

use GenericDatabase\Engine\SQLSrvEngine;

class DSN
{
    public static function parseDsn(): string|\Exception
    {
        if (!extension_loaded('sqlsrv')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                ['sqlsrv', 'PHP.ini']
            );
            throw new \Exception($message);
        }

        $result = null;
        $result = sprintf(
            "sqlsrv://%s:%s@%s:%s/?database=%s&charset=%s%s",
            SQLSrvEngine::getInstance()->getUser(),
            SQLSrvEngine::getInstance()->getPassword(),
            SQLSrvEngine::getInstance()->getHost(),
            SQLSrvEngine::getInstance()->getPort(),
            SQLSrvEngine::getInstance()->getDatabase(),
            SQLSrvEngine::getInstance()->getCharset(),
            Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT) ? '&timeout=' . Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT) : '',
        );

        SQLSrvEngine::getInstance()->setDsn((string) $result);
        return $result;
    }
}
