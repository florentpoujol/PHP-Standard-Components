<?php

namespace StdCmp\Cache;

use \StdCmp\Cache\Interfaces\CacheItem as CacheItemInterface;

class CacheItem implements CacheItemInterface
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

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($defaultValue = null)
    {
        if ($defaultValue !== null && !$this->isHit()) {
            return $defaultValue;
        }
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * {@inheritdoc}
     */
    public function setExpiration($expiration)
    {
        if ($expiration instanceof \DateInterval) {
            $expiration = $expiration->format("%s");
        } elseif ($expiration instanceof \DateTime) {
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

    /**
     * {@inheritdoc}
     */
    public function isHit(bool $isHit = null): bool
    {
        if ($isHit !== null) {
            $this->isHit = $isHit;
        }
        return $this->isHit;
    }

    /**
     * {@inheritdoc}
     */
    public function addTag(string $tag)
    {
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
