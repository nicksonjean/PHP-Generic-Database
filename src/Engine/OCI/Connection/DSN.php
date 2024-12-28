<?php

namespace GenericDatabase\Engine\OCI\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\OCIConnection;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
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
            OCIConnection::getInstance()->getUser(),
            OCIConnection::getInstance()->getPassword(),
            OCIConnection::getInstance()->getHost(),
            OCIConnection::getInstance()->getPort(),
            OCIConnection::getInstance()->getDatabase(),
            OCIConnection::getInstance()->getCharset()
        );

        OCIConnection::getInstance()->setDsn($result);
        return $result;
    }
}
