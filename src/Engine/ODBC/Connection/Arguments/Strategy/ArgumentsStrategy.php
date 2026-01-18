<?php

namespace GenericDatabase\Engine\ODBC\Connection\Arguments\Strategy;

use GenericDatabase\Helpers\Generators;
use GenericDatabase\Interfaces\Connection\IArgumentsStrategy;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\ODBC\Connection\Arguments\ArgumentsHandler;

class ArgumentsStrategy implements IArgumentsStrategy
{
    /**
     * Obtém a instância de conexão a partir de ArgumentsHandler
     */
    public function getInstance(): IConnection
    {
        return ArgumentsHandler::getInstance();
    }

    /**
     * Obtém o manipulador de opções a partir de ArgumentsHandler
     */
    public function getOptionsHandler(): IOptions
    {
        return ArgumentsHandler::getOptionsHandler();
    }

    /**
     * Transform variables in constants
     *
     * @param array $value
     * @return array
     */
    public function setConstant(array $value): array
    {
        $options = Generators::setConstant(
            $value,
            $this->getInstance(),
            'ODBC',
            'ODBC',
            ['ATTR_PERSISTENT', 'ATTR_CONNECT_TIMEOUT', 'ATTR_AUTOCOMMIT', 'DEFAULT_FETCH_MODE']
        );
        $this->getOptionsHandler()->setOptions($options);
        return $this->getOptionsHandler()->getOptions();
    }
}
