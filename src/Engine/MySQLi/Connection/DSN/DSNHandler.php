<?php

namespace GenericDatabase\Engine\MySQLi\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Generic\Connection\Instance;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IDSN;

#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
    use Instance;

    public function parse(): string|Exceptions
    {
        if (!extension_loaded('mysqli')) {
            throw new Exceptions("Invalid or not loaded 'mysqli' extension in PHP.ini settings");
        }

        $this->set(
            'dsn',
            vsprintf(
                "mysql://%s:%s@%s:%s/%s?charset=%s",
                [
                    $this->get('user'),
                    $this->get('password'),
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                    $this->get('charset')
                ]
            )
        );
        return $this->get('dsn');
    }
}
