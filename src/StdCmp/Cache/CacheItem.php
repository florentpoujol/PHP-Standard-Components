<?php

namespace StdCmp\Cache;

use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface, TagAwareItem
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var int
     */
    protected $expiration;

    /**
     * @var bool
     */
    protected $isHit = false;

    /**
     * @var string[]
     */
    protected $tags = [];

    /**
     * @param string|null $key
     * @param mixed|null $value
     * @param int|\DateTime|\DateInterval|null $expiration
     */
    public function __construct(string $key = null, $value = null, $expiration = null)
    {
        $this->key = $key;
        $this->value = $value;
        if ($expiration !== null) {
            $this->setExpiration($expiration);
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function get($defaultValue = null)
    {
        if ($defaultValue !== null && !$this->isHit()) {
            return $defaultValue;
        }
        return $this->value;
    }

    public function set($value)
    {
        $this->value = $value;
        return $this;
    }

    public function expiresAt($expiration)
    {
        $this->setExpiration($expiration);
        return $this;
    }

    public function expiresAfter($time)
    {
        $this->setExpiration($time);
        return $this;
    }

    public function getExpiration()
    {
        return $this->expiration;
    }

    public function setExpiration($expiration)
    {
        if ($expiration instanceof \DateInterval) {
            $expiration = $expiration->format("%s");
        } elseif ($expiration instanceof \DateTimeInterface) {
            $expiration = $expiration->getTimestamp();
        }

        $time = time();
        $expiration = (int)$expiration; // for when string or null

        if ($expiration <= 0) {
          $expiration = null;
        } elseif ($expiration < $time) {
            $expiration += $time; // is a ttl
        }

        $this->expiration = $expiration;
    }

    public function isHit(bool $isHit = null): bool
    {
        if ($isHit !== null) {
            $this->isHit = $isHit;
        }
        return $this->isHit;
    }

    public function addTag(string $tag)
    {
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }

    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}
