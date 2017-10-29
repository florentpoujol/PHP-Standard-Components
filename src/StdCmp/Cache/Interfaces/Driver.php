<?php

namespace StdCmp\Cache\Interfaces;

// PSR-16 SimplCache
interface Driver
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
     * @param int|\DateInterval $ttl
     * @return void
     */
    public function set(string $key, $value, $ttl);

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
