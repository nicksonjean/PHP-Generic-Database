<?php

namespace GenericDatabase\Interfaces\Connection;

/**
 * This interface defines the contract for database connection options.
 *
 * @package PHP-Generic-Database
 * @subpackage Interfaces\Connection
 */
interface IOptions
{
    /**
     * This method is responsible for obtaining all options already defined by the user.
     *
     * @param ?int $type = null
     * @return mixed
     */
    public function getOptions(?int $type = null): mixed;

    /**
     * This method is responsible for set options before connect in database
     *
     * @param ?array $options = null
     * @return void
     */
    public function setOptions(?array $options = null): void;

    /**
     * This method is responsible for set options after connect in database,
     * more information's in https://www.php.net/manual/en/ibase.configuration.php
     *
     * @return void
     */
    public function define(): void;
}
