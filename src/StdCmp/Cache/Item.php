<?php

namespace StdCmp\Cache;

use StdCmp\Cache\Interfaces;

class Item implements Interfaces\Item
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
     * @var \Datetime
     */
    protected $expirationDatetime;

    /**
     * @var bool
     */
    protected $isHit = false;

    public function __construct(string $key = null, $value = null, $expire = null)
    {
        $this->key = $key;
        $this->value = $value;

        if ($expire !== null) {
            $time = time();
            $datetime = new \DateTime();

            if (is_int($expire)) {
                if ($expire < $time) {
                    // expire is a ttl
                    $expire += $time;
                }
                $datetime->setTimestamp($expire);
            } elseif ($expire instanceof \DateInterval) {
                $datetime->add($expire);
            } elseif ($expire instanceof \DateTime) {
                $datetime = $expire;
            }

            $this->expirationDatetime = $datetime;
        }
    }

    /**
     * @param bool|null $isHit
     * @return bool
     */
    public function isHit(bool $isHit = null): bool
    {
        if ($isHit !== null) {
            $this->isHit = $isHit;
        }
        return $this->isHit;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function set($value)
    {
        $this->value = $value;
    }

    /**
     * @param int|\DateTime $time
     * @return \DateTime
     */
    public function expireAt($time)
    {
        if (is_int($time)) {
            $timestamp = $time;
            $time = new \DateTime();
            $time->setTimestamp($timestamp);
        }
        $this->expirationDatetime = $time;
        return $this->expirationDatetime;
    }

    /**
     * @param int|\DateInterval $ttl
     */
    public function expireAfter($ttl)
    {
        if (is_int($ttl)) {
            $ttl = new \DateInterval("PT{$ttl}S");
        }

        if ($this->expirationDatetime === null) {
            $this->expirationDatetime = new \DateTime();
        }

        $this->expirationDatetime->setTimestamp(time());
        $this->expirationDatetime->add($ttl);
    }
}
