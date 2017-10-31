<?php

namespace StdCmp\Cache\Interfaces;

use StdCmp\Cache\Item;

interface ItemPool
{
    /**
     * @param string $key
     * @return Item
     */
    public function getItem(string $key): Item;

    /**
     * @param Item $item
     * @return mixed
     */
    public function setItem(Item $item): bool;

    /**
     * @param Item $item
     * @return mixed
     */
    public function setItemDeferred(Item $item);

    /**
     * @return bool
     */
    public function commit(): bool;

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
     * @return bool
     */
    public function clear(): bool;
}
