<?php

namespace StdCmp\Log\Writers;

use StdCmp\Log\Formatters;

class Stream extends Writer
{
    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $path = "";

    /**
     * @param string|resource $descriptor When a string, $descriptor can be the path of a simple path or a protocol descriptor like file://..., php://..., http://...
     */
    public function __construct($descriptor)
    {
        if (is_resource($descriptor)) {
            $this->resource = $descriptor;
        } else {
            $this->path = $descriptor;

            // todo: needs to check if the whole path exists ?
            $resource = fopen($this->path, "a");
            if ($resource === false) {
                throw new \InvalidArgumentException("Could not open path '$this->path'.");
            }
            $this->resource = $resource;
        }
    }

    /**
     * @param array $record
     * @return bool
     */
    public function __invoke(array $record): bool
    {
        $record = $this->processHelpers($record);
        if ($record === false) {
            return true; // other writers may return false if they want other writers to run
        }

        // format
        if ($this->formatter === null) {
            $this->formatter = new Formatters\Text();
        }

        $formatter = $this->formatter;
        $message = $formatter($record);
        // done in two steps because PHPStorm complain that $this->formatter is not a method

        // write
        fwrite($this->resource, $message);

        if ($this->path !== "") {
            // only close the resource if we created it
            fclose($this->resource);
        }

        return true;
    }
}
