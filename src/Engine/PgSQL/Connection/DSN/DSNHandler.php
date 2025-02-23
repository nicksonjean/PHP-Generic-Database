<?php

namespace GenericDatabase\Engine\PgSQL\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Generic\Connection\Instance;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;
use GenericDatabase\Engine\PgSQL\Connection\Options;


#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
    use Instance;

    public function parse(): string|Exceptions
    {
        if (!extension_loaded('pgsql')) {
            throw new Exceptions("Invalid or not loaded 'pgsql' extension in PHP.ini settings");
        }

        $this->set(
            'dsn',
            vsprintf(
                "host=%s port=%s dbname=%s user=%s password=%s%s options='--client_encoding=%s'",
                [
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                    $this->get('user'),
                    $this->get('password'),
                    Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                        ? ' connect_timeout=' . Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                        : '',
                    $this->get('charset')
                ]
            )
        );
        return $this->get('dsn');
    }
}
