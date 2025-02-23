<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Generic\Connection\Instance;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;
use GenericDatabase\Engine\SQLSrv\Connection\Options;

#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
    use Instance;

    /**
     * @throws Exceptions
     */
    public function parse(): string|Exceptions
    {
        if (!extension_loaded('sqlsrv')) {
            throw new Exceptions("Invalid or not loaded 'sqlsrv' extension in PHP.ini settings");
        }

        $this->set(
            'dsn',
            vsprintf(
                "sqlsrv://%s:%s@%s:%s/?database=%s&charset=%s%s",
                [
                    $this->get('user'),
                    $this->get('password'),
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                    $this->get('charset'),
                    Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                        ? '&timeout=' . Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                        : '',
                ]
            )
        );
        return $this->get('dsn');
    }
}
