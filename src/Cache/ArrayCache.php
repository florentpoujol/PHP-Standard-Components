<?php

namespace StdCmp\Cache;

use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

// does not support expiration
class ArrayCache implements CacheInterface, CacheItemPoolInterface, TagAwareCache
{
    /**
     * @var array [key => mixed]
     */
    protected $store = [];

    /**
     * @var array [tag => string[]]
     */
    protected $keysByTags = [];

    /**
     * @var CacheItemPoolInterface[]
     */
    protected $deferredItems = [];

    public function has($key): bool
    {
        $this->validateKey($key);
        return isset($this->store[$key]);
    }

    public function delete($key): bool
    {
        $this->validateKey($key);
        unset($this->store[$key]);
        $this->removeKeyFromTags($key);
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function clear(): bool
    {
        $this->store = [];
        $this->keysByTags = [];
        return true;
    }

    public function get($key, $defaultValue = null)
    {
        $this->validateKey($key);
        return $this->store[$key] ?? $defaultValue;
    }

    public function getMultiple($keys, $defaultValue = null): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $defaultValue);
        }
        return $values;
    }

    public function set($key, $value, $expiration = null): bool
    {
        $this->validateKey($key);
        $this->store[$key] = $value;
        return true;
    }

    public function setMultiple($values, $expiration = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
        return true;
    }

    // CacheItemPoolInterface

    public function hasItem($key): bool
    {
        return $this->has($key);
    }

    public function deleteItem($key): bool
    {
        return $this->delete($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->deleteMultiple($keys);
    }

    // clear() already defined above, as part of Psr\SimpleCache\CacheInterface

    public function getItem($key): CacheItemInterface
    {
        $this->validateKey($key);
        $item = new CacheItem($key);

        if (isset($this->store[$key])) {
            $item->set($this->store[$key]);
            $item->isHit(true);
            $item->setTags($this->getTagsForKey($key));
        }

        return $item;
    }

    public function getItems(array $keys = []): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getItem($key);
        }
        return $values;
    }

    public function save(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $this->validateKey($key);
        if ($item instanceof TagAwareItem) {
            $this->addKeyToTags($key, $item->getTags());
        }
        return $this->set($key, $item->get());
    }

    /**
     * @param array|CacheItemPoolInterface[] $items [Item] or [key => Item]
     * @return bool Returns true only if all the items have properly been saved.
     */
    public function saveMultiple(array $items): bool
    {
        foreach ($items as $item) {
            $this->save($item);
        }
        return true;
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferredItems[] = $item;
    }

    public function commit()
    {
        $items = $this->deferredItems;
        $this->deferredItems = [];
        return $this->saveMultiple($items);
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
            $this->deleteMultiple($this->keysByTags[$tag]);
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
