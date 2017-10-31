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
     * @param string|null $key
     * @param mixed|null $value
     * @param int|\DateTime|\DateInterval|null $expire
     */
    public function __construct(string $key = null, $value = null, $expire = null);

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
     * @return mixed
     */
    public function get();

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
