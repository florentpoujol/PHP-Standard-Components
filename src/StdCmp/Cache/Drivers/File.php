<?php

namespace StdCmp\Cache\Drivers;

// save the expiration date as the file's modification's time
//
class File
{
    /**
     * @var string
     */
    protected $dirPath;

    public function __construct(string $dirPath)
    {
        $this->dirPath = $dirPath;

        // check that dir exists and is writable
        // try to build it and to write to it
        // otherwise exception
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $defaultValue = null)
    {
        // validate key
        // get path
        // if file_exist
            // if not expired
                // set value as file value
            // else
                // set value as defaultvalue
        // else
            // set value as defaultvalue
        // return value
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, $ttl)
    {
        // validate key
        // transform value
        // write file
            // exception if can not write
        // touch with expiration date
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        // validate key
        // build file path
        // if file exists
            // expiration > current time
                // return true
        // return false
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key)
    {
        // validate key
        // get path
        // if fil_exists
            // unlink
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        // loop on all the files in dir
            // unlink
    }

    protected function validateKey(string $key): bool
    {
        // check length and characters a-zA-Z0-9_.-
        // exception
    }

    protected function getPath(string $key): string
    {

    }
}
