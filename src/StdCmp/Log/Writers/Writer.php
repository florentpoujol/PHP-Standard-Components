<?php

namespace StdCmp\Log\Writers;

use StdCmp\Log\Interfaces;
use StdCmp\Log\Traits;

abstract class Writer implements Interfaces\Writer
{
    use Traits\Helpable;

    /**
     * @var callable
     */
    protected $formatter;

    /**
     * @param callable $formatter
     */
    public function setFormatter(callable $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param array $record
     * @return bool
     */
    abstract public function __invoke(array $record): bool;
}
