<?php

namespace GenericDatabase\Engine\OCI;

use GenericDatabase\Engine\OCIEngine;

class DSN
{
    public static function parseDsn(): string|\Exception
    {
        if (!extension_loaded('oci8')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'interbase',
                'PHP.ini'
            );
            throw new \Exception($message);
        }

        $result = null;
        $result = sprintf(
            "oci8://%s:%s@%s:%s/?service=%s&charset=%s",
            OCIEngine::getInstance()->getUser(),
            OCIEngine::getInstance()->getPassword(),
            OCIEngine::getInstance()->getHost(),
            OCIEngine::getInstance()->getPort(),
            OCIEngine::getInstance()->getDatabase(),
            OCIEngine::getInstance()->getCharset()
        );

        OCIEngine::getInstance()->setDsn((string) $result);
        return $result;
    }
}
