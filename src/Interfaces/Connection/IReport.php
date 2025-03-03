<?php

namespace GenericDatabase\Interfaces\Connection;

interface IReport
{
    public function setReportMode($mode): void;

    public function getReportMode(): mixed;

    public function handleError($message = null): void;
}
