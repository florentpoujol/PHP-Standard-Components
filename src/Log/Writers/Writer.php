<?php

namespace StdCmp\Log\Writers;

use StdCmp\Log\Interfaces;
use StdCmp\Log\Traits;

abstract class Writer implements Interfaces\Writer
{
    use Traits\Helper;

    /**
     * @var callable
     */
    protected $formatter;

    public function setFormatter(callable $formatter)
    {
        $this->formatter = $formatter;
    }

    abstract public function __invoke(array $record): bool;
}
