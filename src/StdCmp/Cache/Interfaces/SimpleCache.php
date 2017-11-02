<?php

namespace StdCmp\Cache\Interfaces;

interface SimpleCache extends CommonCache
{
    /**
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public function getValue(string $key, $defaultValue = null);

    /**
     * @param array $keys
     * @param mixed|null $defaultValue
     * @return array An associative array: key => value
     */
    public function getValues(array $keys, $defaultValue = null): array;

    /**
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $expiration
     * @return bool
     */
    public function setValue(string $key, $value, $expiration = null): bool;

    /**
     * @param array $values An associative array of key => value
     * @param int|\DateInterval|null $expiration
     * @return bool Returns true only if all the values have properly been saved.
     */
    public function setValues(array $values, $expiration = null): bool;
}
