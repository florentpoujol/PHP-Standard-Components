<?php

namespace StdCmp\Cache\Interfaces;

interface CacheItem
{
    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @param string $key
     */
    public function setKey(string $key);

    /**
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public function getValue($defaultValue = null);

    /**
     * @param mixed $value
     * @return void
     */
    public function setValue($value);

    /**
     * @return int|null A timestamp, or null when no expiration has been set
     */
    public function getExpiration();

    /**
     * The expiration is converted to int first, then checked against the current timestamp.
     * Is considered a TTL when < to the current timestamp.
     * Is considered null when <= 0.
     *
     * @param int|\DateTime|\DateInterval|null $expiration
     */
    public function setExpiration($expiration);

    /**
     * @param bool|null $isHit
     * @return bool
     */
    public function isHit(bool $isHit = null): bool;
}
