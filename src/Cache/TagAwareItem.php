<?php

namespace StdCmp\Cache;

interface TagAwareItem
{
    /**
     * @param string $tag
     */
    public function addTag(string $tag);

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags);

    /**
     * @return string[]
     */
    public function getTags(): array;
}
