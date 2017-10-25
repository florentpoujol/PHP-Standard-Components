<?php

namespace StdCmp\Log\Interfaces;

interface Writer
{
    /**
     * @param callable $filter
     * @return void
     */
    public function addFilter(callable $filter);

    /**
     * @return callable[]
     */
    public function getFilters(): array;

    /**
     * @param callable[] $filters
     * @return void
     */
    public function setFilters(array $filters);

    /**
     * @param callable $formatter
     * @return void
     */
    public function setFormatter(callable $formatter);

    /**
     * @param array $record
     * @return void
     */
    public function __invoke(array $record);
}
