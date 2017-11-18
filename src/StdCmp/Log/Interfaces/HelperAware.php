<?php

namespace StdCmp\Log\Interfaces;

interface HelperAware
{
    public function addHelper(callable $helper);

    public function getHelpers(): array;

    public function setHelpers(array $helpers);

    /**
     * Returns the record, altered by all successive processors or false if one filter has returned false.
     * @return array|bool
     */
    public function processHelpers(array $record);
}
