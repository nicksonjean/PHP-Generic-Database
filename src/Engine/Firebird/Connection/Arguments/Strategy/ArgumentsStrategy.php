<?php

namespace GenericDatabase\Engine\Firebird\Connection\Arguments\Strategy;

use GenericDatabase\Helpers\Generators;
use GenericDatabase\Interfaces\Connection\IArgumentsStrategy;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\Firebird\Connection\Arguments\ArgumentsHandler;

/**
 * Class ArgumentsStrategy
 * Implements the IArgumentsStrategy interface to provide methods for obtaining instances and options handlers.
 */
class ArgumentsStrategy implements IArgumentsStrategy
{
    /**
     * Retrieves an instance of IConnection.
     *
     * This method returns an instance of IConnection by calling the getInstance method
     * of the ArgumentsHandler class.
     *
     * @return IConnection An instance of IConnection.
     */
    public function getInstance(): IConnection
    {
        return ArgumentsHandler::getInstance();
    }

    /**
     * Retrieves the options handler.
     *
     * This method returns an instance of IOptions by calling the static method
     * getOptionsHandler() from the ArgumentsHandler class.
     *
     * @return IOptions The options handler instance.
     */
    public function getOptionsHandler(): IOptions
    {
        return ArgumentsHandler::getOptionsHandler();
    }

    /**
     * Sets the constant options for the Firebird connection.
     *
     * This method uses the Generators::setConstant function to set the constant options
     * for the Firebird connection. It then updates the options handler with the new options
     * and returns the updated options.
     *
     * @param array $value The array of values to be set as constants.
     * @return array The updated options after setting the constants.
     */
    public function setConstant(array $value): array
    {
        $options = Generators::setConstant(
            $value,
            $this->getInstance(),
            'Firebird',
            'Firebird',
            ['ATTR_PERSISTENT', 'ATTR_CONNECT_TIMEOUT']
        );
        $this->getOptionsHandler()->setOptions($options);
        return $this->getOptionsHandler()->getOptions();
    }
}

