<?php
/**
 * Created by PhpStorm.
 * User: florent
 * Date: 29/10/17
 * Time: 18:16
 */

namespace StdCmp\Cache\Interfaces;

interface Item
{
    /**
     * @param bool|null $isHit
     * @return bool
     */
    public function isHit(bool $isHit = null): bool;

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @param string $key
     * @return void
     */
    public function setKey(string $key);

    /**
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get($defaultValue = null);

    /**
     * @param mixed $value
     * @return void
     */
    public function set($value);

    /**
     * @param int|\DateTime $time
     */
    public function expireAt($time);

    /**
     * @param int|\DateInterval $ttl
     */
    public function expireAfter($ttl);
}
