<?php

namespace StdCmp\Log\Traits;

trait Helpable
{
    /**
     * @var callable[] Processors and/or filters
     */
    protected $helpers = [];

    /**
     * @param callable $processorOrFilter
     */
    public function addHelper(callable $processorOrFilter)
    {
        $this->helpers[] = $processorOrFilter;
    }

    /**
     * @return array
     */
    public function getHelpers(): array
    {
        return $this->helpers;
    }

    /**
     * @param callable[] $helpers
     * @return void
     */
    public function setHelpers(array $processorsOrFilters)
    {
        $this->helpers = [];
        foreach ($processorsOrFilters as $id => $helper) {
            if (!is_callable($helper)) {
                throw new \UnexpectedValueException("Processor or filter n°$id is a " . gettype($helper) . " instead of a callable.");
            }

            $this->helpers[] = $helper;
        }
    }

    /**
     * Returns the record, altered by all successive processors or false if one filter has returned false.
     * @param array $record
     * @return array|bool
     */
    public function processHelpers(array $record)
    {
        foreach ($this->helpers as $key => $helper) {
            $returnedValue = $helper($record);
            if ($returnedValue === false) {
                return false;
            }

            $type = gettype($returnedValue);
            if ($type !== "boolean") {
                if($type !== "array" || empty($returnedValue)) {
                    throw new \UnexpectedValueException("Helper n°$key returned a value of type '$type' instead of array or an empty array.");
                }

                $record = $returnedValue;
            }
        }

        return $record;
    }
}
