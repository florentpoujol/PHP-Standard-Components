<?php

namespace StdCmp\Cache;

use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

class FileCache implements CacheInterface, CacheItemPoolInterface, TagAwareCache
{
    /**
     * @var string
     */
    protected $dirPath;

    protected $defaultTTL = 31536000; // 1 year

    protected $keysByTags = [];

    protected $tagsFileName = "filecache.tags";

    /**
     * @var CacheItemInterface[]
     */
    protected $deferredItems = [];

    /**
     * @param string $dirPath
     * @param int|\DateInterval|null $defaultTTL
     * @throws \InvalidArgumentException Path does not exists, can't be created and/or is not readable or writable.
     */
    public function __construct(string $dirPath, $defaultTTL = null)
    {
        if (
            // mkdir throws warning when can't create because of permissions
            (!file_exists($dirPath) && !@mkdir($dirPath,  0777, true)) ||
            (!is_readable($dirPath) || !is_writable($dirPath)) ||
            ($dirRealPath = realpath($dirPath)) === false
            // dirRealPath is set here because it fails if the folder does not yet exists
        ) {
            throw new \InvalidArgumentException("Path '$dirPath' does not exists, can't be created and/or is not readable or writable.");
        }

        $this->dirPath =  $dirRealPath . DIRECTORY_SEPARATOR;
        // by now we suppose the directory is writable and readable throughout the object's lifetime

        if ($defaultTTL !== null) {
            if ($defaultTTL instanceof \DateInterval) {
                $defaultTTL = (int)$defaultTTL->format("%s");
            }
            $this->defaultTTL = $defaultTTL;
        }

        $this->loadTagsFile();
    }

    public function __destruct()
    {
        if (!empty($this->deferredItems)) {
            $this->commit();
        }
        if (!empty($this->keysByTags)) {
            $this->writeTagsFile();
        }
    }

    // SimpleCache

    public function has($key): bool
    {
        $this->validateKey($key);
        $path = $this->getPath($key);
        return file_exists($path) && $this->isNotExpired($path);
    }

    public function delete($key): bool
    {
        $this->validateKey($key);
        $path = $this->getPath($key);
        $this->removeKeyFromTags($key);
        return @unlink($path);
    }

    public function deleteMultiple($keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        return $success; // returns false if any of the delete call has returned false
    }

    public function clear()
    {
        $this->keysByTags = [];
        $this->deleteDir($this->dirPath);
        return mkdir($this->dirPath, 0777, true); // will return false is dir exists, meaning that deleteDir() has had an issue
    }

    public function get($key, $defaultValue = null)
    {
        $this->validateKey($key);
        $path = $this->getPath($key);
        $value = $defaultValue;

        if ($this->has($key)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $value = unserialize( $content );
                // possible hack if someone change the content of cache files
                // in case of error, false is returned and E_NOTICE is issued
            }
        }

        return $value;
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
        $path = $this->getPath($key);

        $success = file_put_contents($path, serialize($value) );
        if (!$success) {
            return false;
        }

        // should return true, regardless of the success of touch ?
        return touch($path, $this->expirationToTimestamp($expiration));
    }

    public function setMultiple($values, $expiration = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->set($key, $value, $expiration) && $success;
        }
        return $success;
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

        if ($this->has($key)) {
            $item->set($this->get($key));
            $item->isHit(true);
            $item->setTags($this->getTagsForKey($key));

            $expiration = filemtime($this->getPath($key));
            if ($expiration !== false) {
                $item->setExpiration($expiration);
            }
        }

        return $item;
    }

    public function getItems(array $keys = []): array
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    public function save(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $this->validateKey($key);
        $expiration = $item->getExpiration(); // how are we supposed to get the expiration date with just the CacheItemInterface ??
        if ($item instanceof TagAwareItem) {
            $this->addKeyToTags($key, $item->getTags());
        }
        return $this->set($key, $item->get(), $expiration);
    }

    public function saveMultiple($items): bool
    {
        $success = true;
        foreach ($items as $item) {
            $success = $this->save($item) && $success;
        }
        return $success;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferredItems[] = $item;
        return true;
    }

    public function commit(): bool
    {
        $items = $this->deferredItems;
        $this->deferredItems = [];
        return $this->saveMultiple($items);
    }

    // TagAwareCache

    public function getItemsWithTag(string $tag): array
    {
        $keys = [];
        if (isset($this->keysByTags[$tag])) {
            $keys = array_keys($this->keysByTags[$tag]);
        }
        if (empty($keys)) {
            return $keys;
        }
        return $this->getItems($keys);
    }

    public function hasTag(string $tag): bool
    {
        if (isset($this->keysByTags[$tag])) {
            return count($this->keysByTags[$tag]) > 0;
        }
        return false;
    }

    public function deleteTag(string $tag): bool
    {
        $success = true;
        if (isset($this->keysByTags[$tag])) {
            $success = $this->deleteMultiple($this->keysByTags[$tag]) && $success;
            unset($this->keysByTags[$tag]);
        }
        return $success;
    }

    // protected methods

    /**
     * Recursively delete the specified directory (files + subdirectories)
     * .
     * @param string $basePath Must have a trailing slash.
     */
    protected function deleteDir(string $basePath)
    {
        $resource = opendir($basePath);
        while (($fileName = readdir($resource)) !== false) {
            if ($fileName !== "." && $fileName !== "..") {
                $filePath = $basePath . $fileName;

                if (is_dir($filePath)) {
                    $this->deleteDir($filePath . DIRECTORY_SEPARATOR);
                } else {
                    unlink($filePath);
                }
            }
        }
        closedir($resource);
        rmdir($basePath);
    }

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
     * @return string
     */
    protected function getPath(string $key): string
    {
        return $this->dirPath . $key;
    }

    /**
     * @param string $path
     * @return bool
     * @throws \Exception when file stat are not available.
     */
    protected function isNotExpired(string $path): bool
    {
        $expiration = filemtime($path);
        if ($expiration !== false) {
            return $expiration > time();
        }
        return false;
    }

    /**
     * The expiration is converted to int first, then checked against the current timestamp.
     * Default to the class' default TTL when null  or <= 0.
     * Is considered a TTL when < to the current timestamp.
     *
     * @param int|\DateTime|\DateInterval|null $expiration
     * @return int|null
     */
    protected function expirationToTimestamp($expiration = null)
    {
        if ($expiration instanceof \DateInterval) {
            $expiration = $expiration->format("%s");
        } elseif ($expiration instanceof \DateTime) {
            $expiration = $expiration->getTimestamp();
        }

        $time = time();
        $expiration = (int)$expiration; // for when string or null

        if ($expiration <= 0) {
            $expiration = $this->defaultTTL;
        }
        if ($expiration < $time) { // is a ttl
            $expiration += $time;
        } // else $expiration > $time (is already a timestamp)

        return $expiration;
    }

    /**
     * @param string $key
     * @param array $tags
     */
    protected function addKeyToTags(string $key, array $tags)
    {
        $writeTagsFile = false;
        foreach ($tags as $tag) {
            if (! isset($this->keysByTags[$tag])) {
                $this->keysByTags[$tag] = [];
            }
            if (! isset($this->keysByTags[$tag][$key])) {
                $this->keysByTags[$tag][$key] = true;
                $writeTagsFile = true;
            }
        }
        if ($writeTagsFile) {
            $this->writeTagsFile();
        }
    }

    /**
     * @param string $key
     * @param array|null $tags
     */
    protected function removeKeyFromTags(string $key, array $tags = null)
    {
        $writeTagsFile = false;
        if ($tags === null) {
            $tags = array_keys($this->keysByTags);
        }
        foreach ($tags as $tag) {
            if (isset($this->keysByTags[$tag][$key])) {
                unset($this->keysByTags[$tag][$key]);
                $writeTagsFile = true;
            }
        }
        if ($writeTagsFile) {
            $this->writeTagsFile();
        }
    }

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

    protected function loadTagsFile()
    {
        $path = $this->dirPath . $this->tagsFileName;
        $content = false;
        $keysByTags = false;
        if (file_exists($path)) {
            $content = file_get_contents($path);
        }
        if (is_string($content)) {
            $keysByTags = @unserialize($content); // false + E_NOTICE on error
        }
        if (is_array($keysByTags)) {
            $this->keysByTags = $keysByTags;
        }
    }

    protected function writeTagsFile()
    {
        $path = $this->dirPath . $this->tagsFileName;
        file_put_contents($path, serialize($this->keysByTags));
    }
}
