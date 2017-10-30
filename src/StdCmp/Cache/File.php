<?php

namespace StdCmp\Cache;

class File implements Interfaces\SimpleCache
{
    /**
     * @var string
     */
    protected $dirPath;

    /**
     * @var int
     */
    private $defaultTTL = 31536000; // 1 year

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
    public function set(string $key, $value, $ttl = null)
    {
        $path = $this->validateKey($key);

        file_put_contents($path, serialize($value) );

        if ($ttl === null) {
            $ttl = $this->defaultTTL;
        } elseif($ttl instanceof \DateInterval) {
            $ttl = (int)$ttl->format("%s");
        }
        touch($path, time() + $ttl);
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
    public function delete(string $key)
    {
        $path = $this->validateKey($key);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->deleteDir($this->dirPath);
        mkdir($this->dirPath, 0777, true);
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
