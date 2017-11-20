<?php

namespace StdCmp\Cache;

interface TagAwareCache
{
    /**
     * @param string $tag
     * @return CacheItem[]
     */
    public function getItemsWithTag(string $tag): array;

    /**
     * @param string $tag
     * @return bool
     */
    public function hasTag(string $tag): bool;

    /**
     * @param string $tag
     * @return bool
     */
    public function deleteTag(string $tag): bool;
}
