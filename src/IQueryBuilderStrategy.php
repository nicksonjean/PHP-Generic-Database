<?php

namespace GenericDatabase;

use GenericDatabase\IQueryBuilder;

interface IQueryBuilderStrategy
{
    /**
     * Defines the strategy instance
     *
     * @param IQueryBuilder $strategy
     * @return void
     */
    public function setStrategy(IQueryBuilder $strategy): void;

    /**
     * Get the strategy instance
     *
     * @return IQueryBuilder
     */
    public function getStrategy(): IQueryBuilder;
}