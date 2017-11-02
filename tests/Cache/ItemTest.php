<?php

namespace Cache;

use StdCmp\Cache\CacheItem;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    public function testConstruct()
    {
        $item = new CacheItem("the_key", "the_value");

        $this->assertEquals("the_key", $item->getKey());
        $this->assertEquals("the_value", $item->get());

        $item->setKey("another_key");
        $item->set("another_value");

        $this->assertEquals("another_key", $item->getKey());
        $this->assertEquals("another_value", $item->get());

        $this->assertEquals(false, $item->isHit());
        $item->isHit(true);
        $this->assertEquals(true, $item->isHit());
    }

    public function testExpire()
    {
        $time = time();

        // timestamp
        $item = new CacheItem("the_key", "the_value", $time + 123);
        $expected = new \DateTime();
        $expected->setTimestamp($time + 123);
        $this->assertEquals($expected, $item->expireAt());

        // ttl
        $item = new CacheItem("the_key", "the_value", 123);
        $expected = new \DateTime();
        $expected->setTimestamp($time + 123);
        $this->assertEquals($expected, $item->expireAt());

        // DateTime
        $dt = new \DateTime();
        $dt->setTimestamp($time + 123);
        $item = new CacheItem("the_key", "the_value", $dt);

        $expected = new \DateTime();
        $expected->setTimestamp($time + 123);
        $this->assertEquals($expected, $item->expireAt());

        // DateInterval
        $dt = new \DateInterval("PT123S");
        $dt = \DateInterval::createFromDateString("+123 seconds");
        $item = new CacheItem("the_key", "the_value", $dt);

        $expected = new \DateTime();
        $expected->setTimestamp($time + 123);
        $this->assertEquals($expected->format(DATE_W3C), $item->expireAt()->format(DATE_W3C));
        // note when just comparing the datetime object, there is a difference of less than a second
    }
}
