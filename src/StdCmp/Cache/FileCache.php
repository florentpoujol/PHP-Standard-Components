<?php

namespace StdCmp\Cache;

class FileCache implements
    Interfaces\SimpleCache,
    Interfaces\ItemAwareCache,
    Interfaces\TagAwareCache
{
    /**
     * @var string
     */
    protected $dirPath;

    /**
     * @var int
     */
    protected $defaultTTL = 31536000; // 1 year

    /**
     * @var array
     */
    protected $keysByTags = [];

    /**
     * @var string
     */
    protected $tagsFileName = "filecachetags";

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

    // CommonCache

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);
        $path = $this->getPath($key);
        return file_exists($path) && $this->isNotExpired($path);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);
        $path = $this->getPath($key);
        $this->deleteKeyFromTags($key);

        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(array $keys = null): bool
    {
        if ($keys === null) {
            $this->keysByTags = [];
            $this->deleteDir($this->dirPath);
            return mkdir($this->dirPath, 0777, true); // will return false is dir exists, meaning that deleteDir() has had an issue
        }

        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        return $success; // returns false if any of the delete call has returned false
    }

    // SimpleCache

    /**
     * {@inheritdoc}
     */
    public function getValue(string $key, $defaultValue = null)
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

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setValue(string $key, $value, $expiration = null): bool
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

    /**
     * {@inheritdoc}
     */
    public function setValues(array $values, $expiration = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->setValue($key, $value, $this->expirationToTimestamp($expiration)) && $success;
        }
        return $success;
    }

    // ItemAwareCache

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): Interfaces\CacheItem
    {
        $this->validateKey($key);
        $path = $this->getPath($key);
        $item = new CacheItem($key);

        if ($this->has($key)) {
            $item->setValue($this->getValue($key));
            $item->isHit(true);

            $expiration = filemtime($path);
            if ($expiration !== false) {
                $item->setExpiration($expiration);
            }
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys): array
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function setItem(Interfaces\CacheItem $item): bool
    {
        return $this->setValue($item->getKey(), $item->getValue(), $item->getExpiration());
    }

    /**
     * {@inheritdoc}
     */
    public function setItems(array $items): bool
    {
        $success = true;
        foreach ($items as $item) {
            $success = $this->setValue($item->getKey(), $item->getValue(), $item->getExpiration()) && $success;
        }
        return $success;
    }

    // TagAwareCache

    /**
     * {@inheritdoc}
     */
    public function getItemsWithTag(string $tag): array
    {
        $keys = $this->keysByTags[$tag] ?? [];
        return $this->getItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsWithTags(array $tags): array
    {
        $items = [];
        foreach ($tags as $tag) {
            $items = array_merge($items, $this->getItemsWithTag($tag));
        }
        return array_unique($items);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTag(string $tag): bool
    {
        return isset($this->keysByTags[$tag]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTags(array $tags): array
    {
        $_tags = [];
        foreach ($tags as $tag) {
            $_tags[$tag] = $this->hasTag($tag);
        }
        return $_tags;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTag(string $tag): bool
    {
        $success = false;

        if (isset($this->keysByTags[$tag])) {
            $success = $this->deleteAll($this->keysByTags[$tag]);
            unset($this->keysByTags[$tag]);
        }

        return $success;
    }

    public function deleteTags(array $tags): bool
    {
        $keys = [];

        foreach ($tags as $tag) {
            if (isset($this->keysByTags[$tag])) {
                $_keys = $this->keysByTags[$tag];
                $keys = array_merge(
                    $keys,
                    $this->keysByTags[$tag]
                );
                unset($this->keysByTags[$tag]);
            }
        }

        return $this->deleteAll(array_unique($keys));
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
     */
    protected function deleteKeyFromTags(string $key)
    {
        foreach ($this->keysByTags as $id => $keys) {
            $offset = array_search($key, $keys);
            if ($offset !== false) {
                array_splice($this->keysByTags[$id], $offset, 1);
            }
        }
    }

    protected function loadTagsFile()
    {
        $path = $this->dirPath . $this->tagsFileName;
        $content = false;
        $keysByTags = false;
        if (file_exists($path)) {
            $content = file_get_contents($path);
        }
        if (is_string($content )) {
            $keysByTags = unserialize($content); // false + E_NOTICE on error
        }
        if (is_array($keysByTags)) {
            $this->keysByTags = $keysByTags;
        }
    }

    protected function saveTagsFile()
    {
        $path = $this->dirPath . $this->tagsFileName;
        file_put_contents($path, serialize($this->keysByTags));
    }
}
