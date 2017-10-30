<?php

namespace StdCmp\Cache\Interfaces;

// PSR-16 SimplCache
interface SimpleCache
{
    /**
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public function get(string $key, $defaultValue = null);

    /**
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $ttl
     * @return void
     */
    public function set(string $key, $value, $ttl = null);

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string $key
     * @return void
     */
    public function delete(string $key);

    /**
     * @return void
     */
    public function clear();
}
