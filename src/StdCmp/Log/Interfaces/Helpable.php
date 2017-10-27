<?php

namespace StdCmp\Log\Interfaces;

interface Helpable
{
    /**
     * @param callable $helper
     * @return void
     */
    public function addHelper(callable $helper);

    /**
     * @return array
     */
    public function getHelpers(): array;

    /**
     * @param callable[] $helpers
     * @return void
     */
    public function setHelpers(array $helpers);

    /**
     * Returns the record, altered by all successive processors or false if one filter has returned false.
     * @param array $record
     * @return array|bool
     */
    public function processHelpers(array $record);
}
