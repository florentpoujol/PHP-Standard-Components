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
     * @param string[] $keys
     * @param mixed|null $defaultValue
     * @return mixed[]
     */
    public function getMultiple(array $keys, $defaultValue = null): array;

    /**
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $ttl
     * @return bool
     */
    public function set(string $key, $value, $ttl = null): bool;

    /**
     * @param array $values
     * @param int|\DateInterval $ttl
     * @return bool
     */
    public function setMultiple(array $values, $ttl = null): bool;

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * @param string[] $keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * @return bool
     */
    public function clear(): bool;
}
