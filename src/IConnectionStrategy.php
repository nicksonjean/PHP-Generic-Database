<?php

namespace GenericDatabase;

use GenericDatabase\IConnection;

interface IConnectionStrategy
{
/**
     * Defines the strategy instance
     *
     * @param IConnection $strategy
     * @return void
     */
    public function setStrategy(IConnection $strategy): void;

    /**
     * Get the strategy instance
     *
     * @return IConnection
     */
    public function getStrategy(): IConnection;
}
