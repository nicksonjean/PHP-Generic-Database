<?php

namespace GenericDatabase\Engine\Firebird\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Generic\Connection\Instance;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IDSN;

#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
    use Instance;

    public function parse(): string|Exceptions
    {
        if (!extension_loaded('interbase')) {
            throw new Exceptions("Invalid or not loaded 'interbase' extension in PHP.ini settings");
        }

        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $this->set(
            'dsn',
            vsprintf(
                "firebird://%s:%s@%s:%s//%s?charset=%s",
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
