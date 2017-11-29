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
        $this->assertEquals($time + 123, $item->getExpiration());

        // ttl
        $item = new CacheItem("the_key", "the_value", 123);
        $expected = $time + 123;
        $this->assertEquals($expected, $item->getExpiration());

        // DateTime
        $dt = new \DateTime();
        $dt->setTimestamp($time + 123);
        $item = new CacheItem("the_key", "the_value", $dt);

        $expected = $time + 123;
        $this->assertEquals($expected, $item->getExpiration());

        // DateInterval
        $dt = new \DateInterval("PT123S");
        $dt = \DateInterval::createFromDateString("+123 seconds");
        $item = new CacheItem("the_key", "the_value", $dt);

        $expected = $time + 123;
        $this->assertEquals($expected, $item->getExpiration());
        // note when just comparing the datetime object, there is a difference of less than a second
    }
}
