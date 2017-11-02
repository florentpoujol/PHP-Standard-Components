<?php

namespace StdCmp\Cache\Interfaces;

interface TagAwareCache
{
    /**
     * @param string $tag
     * @return CacheItem[]
     */
    public function getItemsWithTag(string $tag): array;

    /**
     * @param array $tags
     * @return array
     */
    public function getItemsWithTags(array $tags): array;

    /**
     * @param string $tag
     * @return bool
     */
    public function hasTag(string $tag): bool;

    /**
     * @param string[] $tags
     * @return array
     */
    public function hasTags(array $tags): array;

    /**
     * @param string $tag
     * @return bool
     */
    public function deleteTag(string $tag): bool;

    /**
     * @param string[] $tags
     * @return bool
     */
    public function deleteTags(array $tags): bool;
}
