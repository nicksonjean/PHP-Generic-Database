<?php

namespace GenericDatabase\Engine\SQLSrv\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLSrvConnection;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
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
            SQLSrvConnection::getInstance()->getUser(),
            SQLSrvConnection::getInstance()->getPassword(),
            SQLSrvConnection::getInstance()->getHost(),
            SQLSrvConnection::getInstance()->getPort(),
            SQLSrvConnection::getInstance()->getDatabase(),
            SQLSrvConnection::getInstance()->getCharset(),
            Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                ? '&timeout=' . Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                : '',
        );

        SQLSrvConnection::getInstance()->setDsn($result);
        return $result;
    }
}
