<?php

namespace StdCmp\Cache\Interfaces;

interface CommonCache
{
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
     * Delete the keys specified in the array.
     * Delete the whole cache only if no array is provided.
     *
     * @param string[]|null $keys
     * @return bool Returns true only if all of the keys have been properly deleted.
     */
    public function deleteAll(array $keys = null): bool;
}
