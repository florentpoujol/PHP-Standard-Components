<?php

namespace StdCmp\Log\Writers;

use StdCmp\Log\Interfaces;

abstract class Writer implements Interfaces\Writer
{
    /**
     * The list of filters for this writer.
     *
     * @var callable[]
     */
    protected $filters = [];

    /**
     * The formatter for this writer.
     *
     * @var callable
     */
    protected $formatter;

    /**
     * @param callable $filter
     */
    public function addFilter(callable $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @return callable[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param callable[] $filters
     */
    public function setFilters(array $filters)
    {
        $this->filters = [];
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $this->filters[] = $filter;
            }
        }
    }

    /**
     * @param callable $formatter
     */
    public function setFormatter(callable $formatter)
    {
        if (is_callable($formatter)) {
            $this->formatter = $formatter;
        }
    }

    /**
     * @param array $record
     * @return void
     */
    abstract public function __invoke(array $record);
}
