<?php

namespace StdCmp\Log\Writers;

use StdCmp\Log\Formatters;

class Stream extends Writer
{
    /**
     * @var resource
     */
    protected $resource;

    protected $path = "";

    /**
     * @param string|resource $descriptor When a string, $descriptor can be a path or a protocol descriptor like file://..., php://..., http://...
     */
    public function __construct($descriptor)
    {
        if (is_resource($descriptor)) {
            $this->resource = $descriptor;
            return;
        }

        $path = str_replace("file://", "", $descriptor);
        if (strpos($path, "://") === false) { // $path is actual file path
            $path = realpath($path);
            if ($path === false) {
                throw new \InvalidArgumentException("Could not get realpath of the provided path '$descriptor'");
            }

            $dirname = dirname($path);
            if (!file_exists($dirname)) {
                mkdir($dirname, 0777, true);
            }
            $descriptor = $path;
        }

        $this->path = $descriptor;

        $resource = fopen($this->path, "a");
        if ($resource === false) {
            throw new \InvalidArgumentException("Could not open stream at path '$this->path'.");
        }
        $this->resource = $resource;
    }

    public function __invoke(array $record): bool
    {
        $record = $this->processHelpers($record);
        if ($record === false) {
            return true;
        }

        // format
        if ($this->formatter === null) {
            $this->formatter = new Formatters\Text();
        }
        $message = ($this->formatter)($record);

        // write
        fwrite($this->resource, $message);

        if ($this->path !== "") {
            // only close the resource if we created it
            fclose($this->resource);
        }
        return true;
    }
}
