<?php

namespace StdCmp\Cache;

use StdCmp\Cache\Interfaces\CacheItem as CacheItemInterface;
use StdCmp\Cache\CacheItem;
use StdCmp\Cache\Interfaces\ItemAwareCache;
use StdCmp\Cache\Interfaces\SimpleCache;
use StdCmp\Cache\Interfaces\TagAwareCache;

// does not support expiration
class ArrayCache implements SimpleCache, ItemAwareCache, TagAwareCache
{
    /**
     * @var array key => mixed
     */
    protected $store = [];

    /**
     * @var array tag => string[]
     */
    protected $keysByTags = [];

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);
        return isset($this->store[$key]);
    }

    /**
     * Returns an associative array : key => (has in cache)
     * @param string[] $keys
     * @return array
     */
    public function getHits(array $keys): array
    {
        $hits = [];
        foreach ($keys as $key) {
            $hits[$key] = $this->has($key);
        }
        return $hits;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);
        unset($this->store[$key]);
        $this->removeKeyFromTags($key);
        return true;
    }

    /**
     * Delete the keys specified in the array.
     * Delete the whole cache only if no array is provided.
     *
     * @param string[]|null $keys
     * @return bool Returns true only if all of the keys have been properly deleted.
     */
    public function deleteAll(array $keys = null): bool
    {
        if ($keys === null) {
            $this->store = [];
            $this->keysByTags = [];
            return true;
        }

        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public function getValue(string $key, $defaultValue = null)
    {
        $this->validateKey($key);
        return $this->store[$key] ?? $defaultValue;
    }

    /**
     * @param array $keys
     * @param mixed|null $defaultValue
     * @return array An associative array: key => value
     */
    public function getValues(array $keys, $defaultValue = null): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getValue($key, $defaultValue);
        }
        return $values;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $expiration
     * @return bool
     */
    public function setValue(string $key, $value, $expiration = null): bool
    {
        $this->validateKey($key);
        $this->store[$key] = $value;
        return true;
    }

    /**
     * @param array $values An associative array of key => value
     * @param int|\DateInterval|null $expiration
     * @return bool Returns true only if all the values have properly been saved.
     */
    public function setValues(array $values, $expiration = null): bool
    {
        foreach ($values as $key => $value) {
            $this->setValue($key, $value);
        }
        return true;
    }

    /**
     * @param string $key
     * @return CacheItem
     */
    public function getItem(string $key): CacheItemInterface
    {
        // todo: I don't understand why PHPStorm wants the return type to be the interface when the object implements the interface
        $this->validateKey($key);
        $item = new CacheItem($key);

        if (isset($this->store[$key])) {
            $item->setValue($this->store[$key]);
            $item->isHit(true);
            $item->setTags($this->getTagsForKey($key));
        }

        return $item;
    }

    /**
     * @param string[] $keys
     * @return array An associative array: key => CacheItem
     */
    public function getItems(array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getItem($key);
        }
        return $values;
    }

    /**
     * @param CacheItem $item
     * @return bool
     */
    public function setItem(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $this->validateKey($key);
        $this->addKeyToTags($key, $item->getTags());
        return $this->setValue($key, $item->getValue());
    }

    /**
     * @param array $items Associative array key => Item  or normal array Item[]
     * @return bool Returns true only if all the items have properly been saved.
     */
    public function setItems(array $items): bool
    {
        foreach ($items as $item) {
            $this->setItem($item);
        }
        return true;
    }

    // TagAwareCache

    /**
     * @param string $tag
     * @return CacheItemInterface[]
     */
    public function getItemsWithTag(string $tag): array
    {
        $keys = $this->keysByTags[$tag] ?? [];
        if (empty($keys)) {
            return $keys;
        }
        return $this->getItems($keys);
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function hasTag(string $tag): bool
    {
        $keys = $this->keysByTags[$tag] ?? [];
        return count($keys) > 0;
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function deleteTag(string $tag): bool
    {
        if (isset($this->keysByTags[$tag])) {
            $this->deleteAll($this->keysByTags[$tag]);
            unset($this->keysByTags[$tag]);
        }
        return true;
    }

    // protected

    /**
     * @param string $key
     * @throws \InvalidArgumentException when $key has the wrong character set
     */
    protected function validateKey(string $key)
    {
        if (preg_match("/^[a-zA-Z0-9_\.-]+$/", $key) !== 1) {
            throw new \InvalidArgumentException("Key '$key' has invalid characters. Must be any of these: a-z A-Z 0-9 _ . -");
        }
    }

    /**
     * @param string $key
     * @param array $tags
     */
    protected function addKeyToTags(string $key, array $tags)
    {
        foreach ($tags as $tag) {
            if (! isset($this->keysByTags[$tag])) {
                $this->keysByTags[$tag] = [];
            }
            $this->keysByTags[$tag][$key] = null;
        }
    }

    /**
     * @param string $key
     * @param array|null $tags
     */
    protected function removeKeyFromTags(string $key, array $tags = null)
    {
        if ($tags === null) {
            $tags = array_keys($this->keysByTags);
        }
        foreach ($tags as $tag) {
            unset($this->keysByTags[$tag][$key]);
        }
    }

    /**
     * @param string $key
     * @return array
     */
    protected function getTagsForKey(string $key): array
    {
        $tags = [];
        foreach ($this->keysByTags as $tag => $keys) {
            if (isset($keys[$key])) {
                $tags[] = $tag;
            }
        }
        return $tags;
    }
}
