<?php

namespace StdCmp\Log\Interfaces;

interface Writer extends HelperAware
{
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
