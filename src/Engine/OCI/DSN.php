<?php

namespace GenericDatabase\Engine\OCI;

use AllowDynamicProperties;
use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parseDsn(): string|CustomException
    {
        if (!extension_loaded('oci8')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'interbase',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        $result = sprintf(
            "oci8://%s:%s@%s:%s/?service=%s&charset=%s",
            OCIEngine::getInstance()->getUser(),
            OCIEngine::getInstance()->getPassword(),
            OCIEngine::getInstance()->getHost(),
            OCIEngine::getInstance()->getPort(),
            OCIEngine::getInstance()->getDatabase(),
            OCIEngine::getInstance()->getCharset()
        );

        OCIEngine::getInstance()->setDsn($result);
        return $result;
    }
}
