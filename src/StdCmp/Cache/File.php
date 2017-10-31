<?php

namespace StdCmp\Cache;

class File implements Interfaces\SimpleCache, Interfaces\ItemPool
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
     * @var File[]
     */
    protected $items = [];

    /**
     * @param string $dirPath
     * @param int|\DateInterval|null $defaultTTL
     * @throws \Exception Path can't be worked with
     */
    public function __construct(string $dirPath, $defaultTTL = null)
    {
        if (
            (!file_exists($dirPath) && !@mkdir($dirPath,  0777, true)) || // dir don't exists and can't build it
            // mkdir throws warning when can't create because of permissions
            (!is_readable($dirPath) || !is_writable($dirPath)) || // dir exist but not readable or witable
            ($dirRealPath = realpath($dirPath)) === false
        ) {
            throw new \Exception("Path '$dirPath' does not exists, can't be created and/or is not readable or writable.");
        }
        $this->dirPath =  $dirRealPath . DIRECTORY_SEPARATOR;
        // by now we suppose the directory is writable and readable throughout the object's lifetime

        if ($defaultTTL !== null) {
            if ($defaultTTL instanceof \DateInterval) {
                $defaultTTL = (int)$defaultTTL->format("%s");
            }
            $this->defaultTTL = $defaultTTL;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $defaultValue = null)
    {
        $path = $this->validateKey($key);
        $value = $defaultValue;

        if ($this->has($key)) {
            $value = unserialize( file_get_contents($path) );
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): Item
    {
        $path = $this->validateKey($key);
        $item = new Item($key);

        if ($this->has($key)) {
            $item->set($this->get($key));
            $item->isHit(true);
            $item->expireAt(filemtime($path));
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        $path = $this->validateKey($key);

        $success = file_put_contents($path, serialize($value) );
        if (!$success) {
            return false;
        }

        if ($ttl === null) {
            $ttl = $this->defaultTTL;
        } elseif($ttl instanceof \DateInterval) {
            $ttl = (int)$ttl->format("%s");
        }
        $success = touch($path, time() + $ttl);
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function setItem(Item $item): bool
    {
        $ttl = null;
        $dt = $item->expireAt();
        if ($dt !== null) {
            $ttl = $dt->getTimestamp() - time();
        }

        return $this->set($item->getKey(), $item->get(), $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function setItemDeferred(Item $item): bool
    {
        $key = $item->getKey();
        $path = $this->validateKey($key);

        $ttl = $this->defaultTTL;
        $dt = $item->expireAt();
        if ($dt !== null) {
            $ttl = $dt->getTimestamp() - time();
        }

        $this->items[$key] = [
            "value" => serialize( $item->get() ),
            "path" => $path,
            "ttl" => $ttl
        ];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $time = time();

        foreach ($this->items as $key => $item) {
            $path = $item["path"];

            file_put_contents($path, $item["value"]);
            touch($path, $time + $item["ttl"]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $path = $this->validateKey($key);
        return file_exists($path) && $this->isNotExpired($path);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $path = $this->validateKey($key);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->deleteDir($this->dirPath);
        return mkdir($this->dirPath, 0777, true); // will return false is dir exists, meaning that deleteDir() has had an issue
    }

    protected function deleteDir(string $basePath = null)
    {
        if ($basePath === null) {
            $basePath = $this->dirPath;
        }

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
     * @return string
     */
    protected function validateKey(string $key): string
    {
        if (preg_match("/^[a-zA-Z0-9_\.-]+$/", $key) !== 1) {
            throw new \InvalidArgumentException("Key '$key' has invalid characters.");
        }
        return $this->getPath($key);
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
        return filemtime($path) > time();
    }
}
