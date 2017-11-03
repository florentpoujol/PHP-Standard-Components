<?php

namespace StdCmp\Cache\Interfaces;

interface ItemAwareCache extends CommonCache
{
    /**
     * @param string $key
     * @return CacheItem
     */
    public function getItem(string $key): CacheItem;

    /**
     * @param string[] $keys
     * @return array An associative array: key => CacheItem
     */
    public function getItems(array $keys): array;

    /**
     * @param CacheItem $item
     * @return bool
     */
    public function setItem(CacheItem $item): bool;

    /**
     * @param array $items
     * @return bool Returns true only if all the items have properly been saved.
     */
    public function setItems(array $items): bool;
}
