<?php

namespace GenericDatabase\Engine\PDO\Connection\Arguments\Strategy;

use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Helpers\Validations;
use GenericDatabase\Interfaces\Connection\IArgumentsStrategy;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\PDO\Connection\Arguments\ArgumentsHandler;

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
        $result = [];
        foreach (Arrays::recombine(...$value) as $key => $value) {
            if (Validations::isNumber($value) && !Validations::isBoolean($value)) {
                $result[constant($key)] = (int) $value;
            } elseif (Validations::isBoolean($value)) {
                $result[constant($key)] = (bool) $value;
            } else {
                $result[constant($key)] = constant($value);
            }
        }
        return $result;
    }
}
