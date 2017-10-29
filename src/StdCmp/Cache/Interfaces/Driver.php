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
     * @param string[] $keys
     * @param mixed $defaultValue
     * @return string[]
     */
    public function getMultiple(array $keys, $defaultValue = null): array;

    /**
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval $ttl
     * @return void
     */
    public function set(string $key, $value, $ttl);

    /**
     * @param array $values
     * @param int|\DateInterval $ttl
     * @return void
     */
    public function setMultiple(array $values, $ttl);

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string[] $keys
     * @return string[]
     */
    public function hasMultiple(array $keys): array;

    /**
     * @param string $key
     * @return void
     */
    public function delete(string $key);

    /**
     * @param array $keys
     * @return void
     */
    public function deleteMultiple(array $keys);

    /**
     * @return void
     */
    public function clear();
}
