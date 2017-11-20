<?php

namespace Cache;

use PDO;
use function Sodium\crypto_aead_chacha20poly1305_encrypt;
use StdCmp\Cache\FileCache;
use PHPUnit\Framework\TestCase;
use StdCmp\Cache\CacheItem;
use StdCmp\Cache\PDOCache;

class PDOTest extends TestCase
{
    /**
     * @var PDO
     */
    protected static $spdo;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var PDOCache
     */
    protected $cache;

    public static function setUpBeforeClass()
    {
        self::$spdo = new PDO("sqlite::memory:", null, null, [
            PDO::ERRMODE_EXCEPTION => true,
        ]);
    }

    public function setUp()
    {
        $this->pdo = self::$spdo;
        $this->cache = new PDOCache(self::$spdo);
    }

    // $stmt = "SELECT key, expiration FROM pdo_cache WHERE key = ? AND expiration > ?";
    // $query = $this->pdo->prepare($stmt);
    // $success = $query->execute(["int", time()]);
    // var_dump(time(), $query->fetch());

    public function testSetValue()
    {
        $success = $this->cache->set("int", 123, 123);
        $this->assertEquals(true, $success);

        $success = $this->cache->set("float", 12.3, 123);
        $this->assertEquals(true, $success);

        // without expiration
        $success = $this->cache->set("bool", true);
        $this->assertEquals(true, $success);

        $success = $this->cache->set("string", "a string");
        $this->assertEquals(true, $success);

        // manually expire the "float" key
        $this->pdo->query("UPDATE pdo_cache SET expiration = '" . (time() - 123) ."' WHERE key = 'float'");
    }

    public function testHas()
    {
        $query = $this->pdo->query("SELECT * FROM pdo_cache");
        $entries = $query->fetchAll();
        $this->assertEquals(4, count($entries));


        $value = $this->cache->has("int");
        $this->assertEquals(true, $value);

        $value = $this->cache->has("float");
        $this->assertEquals(false, $value); // entry exists but is expired

        $value = $this->cache->has("bool");
        $this->assertEquals(true, $value);

        $value = $this->cache->has("string");
        $this->assertEquals(true, $value);

        $value = $this->cache->has("non_existant_key");
        $this->assertEquals(false, $value);
    }

    public function testGetValue()
    {
        $value = $this->cache->get("int");
        $this->assertEquals(123, $value);

        $value = $this->cache->get("float");
        $this->assertEquals(null, $value); // expired

        $value = $this->cache->get("bool");
        $this->assertEquals(true, $value);

        $value = $this->cache->get("string");
        $this->assertEquals("a string", $value);

        $value = $this->cache->get("non_existant_key");
        $this->assertEquals(null, $value);
    }

    public function testOverrideValue()
    {
        $this->assertEquals(false, $this->cache->has("float"));
        $this->assertEquals(null, $this->cache->get("float"));

        // override value and reset the expiration time
        $value = $this->cache->set("float", 1.23);
        $this->assertEquals(true, $value);
        $this->assertEquals(1.23, $this->cache->get("float"));
        $this->assertEquals(1.23, $this->cache->get("float"));
    }

    public function testDeleteMultiple()
    {
        $this->cache->set("int", 123);
        $this->cache->set("int2", 123);

        $this->assertEquals(true, $this->cache->has("int"));
        $this->assertEquals(true, $this->cache->has("int2"));
        $this->assertEquals(true, $this->cache->has("bool"));

        $this->cache->deleteMultiple(["int", "int2", "bool"]);

        $this->assertEquals(false, $this->cache->has("int"));
        $this->assertEquals(false, $this->cache->has("int2"));
        $this->assertEquals(false, $this->cache->has("bool"));
    }

    public function testClear()
    {
        $this->cache->set("int", 123);
        $this->cache->set("int2", 123);
        $this->cache->set("float1", 12.3);

        $this->assertEquals(true, $this->cache->has("int"));
        $this->assertEquals(true, $this->cache->has("int2"));
        $this->assertEquals(true, $this->cache->has("float1"));

        $this->cache->clear();

        $this->assertEquals(false, $this->cache->has("int"));
        $this->assertEquals(false, $this->cache->has("int2"));
        $this->assertEquals(false, $this->cache->has("float1"));

        $query = $this->pdo->query("SELECT * FROM pdo_cache");
        $query->execute();
        $entries = $query->fetchAll();
        $this->assertEquals(0, count($entries));
    }

    public function testSetMultiple()
    {
        $this->cache->clear();

        $this->assertEquals(false, $this->cache->has("int"));
        $this->assertEquals(false, $this->cache->has("float"));
        $this->assertEquals(false, $this->cache->has("string"));
        $this->assertEquals(false, $this->cache->has("bool"));

        $values = [
            "int" => 456,
            "float" => 456.789,
        ];
        $this->cache->setMultiple($values);

        $values = [
            "string" => "another string",
            "bool" => false,
        ];
        $this->cache->setMultiple($values, 123);

        $this->assertEquals(true, $this->cache->has("int"));
        $this->assertEquals(true, $this->cache->has("float"));
        $this->assertEquals(true, $this->cache->has("string"));
        $this->assertEquals(true, $this->cache->has("bool"));
    }

    function testGetMultiple()
    {
        $this->assertEquals(true, $this->cache->has("float"));

        // manually expire the "float" key
        $this->pdo->query("UPDATE pdo_cache SET expiration = '" . (time() - 123) ."' WHERE key = 'float'");

        $this->assertEquals(true, $this->cache->has("int"));
        $this->assertEquals(false, $this->cache->has("float"));
        $this->assertEquals(true, $this->cache->has("string"));
        $this->assertEquals(true, $this->cache->has("bool"));
        $this->assertEquals(false, $this->cache->has("non_existant_key"));

        $keys = ["int", "float", "string", "bool", "non_existant_key"];
        $values = $this->cache->getMultiple($keys, "default value");

        $this->assertEquals(5, count($values));
        $this->assertArrayHasKey("int", $values);
        $this->assertArrayHasKey("float", $values);
        $this->assertArrayHasKey("string", $values);
        $this->assertArrayHasKey("bool", $values);
        $this->assertArrayHasKey("non_existant_key", $values);

        $this->assertEquals(456, $values["int"]);
        $this->assertEquals("default value", $values["float"]);
        $this->assertEquals("another string", $values["string"]);
        $this->assertEquals(false, $values["bool"]);
        $this->assertEquals("default value", $values["non_existant_key"]);
    }

    function testSetItem()
    {
        $this->cache->clear();

        $item = new CacheItem("int_item", 798, 123);
        $this->cache->save($item);

        $value = $this->cache->get("int_item");
        $this->assertEquals(798, $value);

        $item = new CacheItem("float_item", 798.123);
        $this->cache->save($item);
        $value = $this->cache->get("float_item");
        $this->assertEquals(798.123, $value);
    }

    function testSetItems()
    {
        $items = [
            new CacheItem("string_item", "an item value", 123),
            new CacheItem("bool_item", true)
        ];

        $this->cache->saveMultiple($items);

        $value = $this->cache->get("string_item");
        $this->assertEquals("an item value", $value);
        $value = $this->cache->get("bool_item");
        $this->assertEquals(true, $value);
    }

    function testGetItem()
    {
        $item = $this->cache->getItem("int_item");
        $item2 = new CacheItem("int_item", 798, 123);
        $this->assertEquals($item, $item2);

        $item = $this->cache->getItem("bool_item");
        $item2 = new CacheItem("bool_item", true);
        $this->assertEquals($item, $item2);

        $item = $this->cache->getItem("non_existant_key");
        $item2 = new CacheItem("non_existant_key");
        $this->assertEquals($item, $item2);
    }

    function getItems()
    {
        $items = $this->cache->getItems(["float_item", "string_item", "non_existant_key"]);
        $this->assertEquals(3, count($items));

        $item = new CacheItem("float_item", 798.123);
        $this->assertEquals($item, $items["float_item"]);
        $this->assertEquals(null, $item->getExpiration());

        $item = new CacheItem("string_item", "a item value");
        $this->assertEquals($item, $items["string_item"]);
        $this->assertEquals(time() + 123, $item->getExpiration());

        $item = new CacheItem("non_existant_key");
        $this->assertEquals($item, $items["non_existant_key"]);
        $this->assertEquals(null, $item->get());
        $this->assertEquals(null, $item->getExpiration());
    }

    function testSetItemWithTag()
    {
        $this->cache->clear();

        $item = new CacheItem("int_item", 798, 123);
        $item->addTag("a_tag");
        $this->cache->save($item);

        $query = $this->pdo->query("SELECT * FROM pdo_cache WHERE key = 'int_item'");
        $entry = $query->fetch();
        $tags = json_decode($entry["tags"], true);
        $this->assertEquals(1, count($tags));
        $this->assertEquals("a_tag", $tags[0]);

        // 2 tags
        $item = new CacheItem("float_item", 798.123);
        $item->addTag("a_tag");
        $item->addTag("another_tag");
        $this->cache->save($item);

        $query = $this->pdo->query("SELECT * FROM pdo_cache WHERE key = 'float_item'");
        $entry = $query->fetch();
        $tags = json_decode($entry["tags"], true);
        $this->assertEquals(2, count($tags));
        $this->assertEquals("a_tag", $tags[0]);
        $this->assertEquals("another_tag", $tags[1]);
    }

    function testHasTag()
    {
        $value = $this->cache->hasTag("a_tag");
        $this->assertEquals(true, $value);

        $value = $this->cache->hasTag("another_tag");
        $this->assertEquals(true, $value);

        $value = $this->cache->hasTag("non_existant_tag");
        $this->assertEquals(false, $value);
    }

    function testItemAreGetWithTheirTag()
    {
        $item = $this->cache->getItem("int_item");
        $tags = $item->getTags();
        $this->assertEquals(1, count($tags));
        $this->assertEquals("a_tag", $tags[0]);

        $item = $this->cache->getItem("float_item");
        $tags = $item->getTags();
        $this->assertEquals(2, count($tags));
        $this->assertEquals("a_tag", $tags[0]);
        $this->assertEquals("another_tag", $tags[1]);
    }

    function testGetItemsWithTag()
    {
        $items = $this->cache->getItemsWithTag("a_tag");
        $this->assertEquals(2, count($items));
        $this->assertArrayHasKey("int_item", $items);
        $this->assertArrayHasKey("float_item", $items);

        $items = $this->cache->getItemsWithTag("another_tag");
        $this->assertEquals(1, count($items));
        $this->assertArrayHasKey("float_item", $items);

        $items = $this->cache->getItemsWithTag("non_existant_tag");
        $this->assertEquals(0, count($items));
    }

    function testDelete()
    {
        $this->assertEquals(true, $this->cache->has("int_item"));
        $this->assertEquals(true, $this->cache->has("float_item"));
        $this->assertEquals(false, $this->cache->has("non_existant_item"));

        $this->assertEquals(true, $this->cache->hasTag("a_tag"));
        $this->assertEquals(true, $this->cache->hasTag("another_tag"));
        $this->assertEquals(false, $this->cache->hasTag("non_existant_tag"));

        $this->cache->deleteTag("non_existant_tag");

        $this->assertEquals(true, $this->cache->has("int_item"));
        $this->assertEquals(true, $this->cache->has("float_item"));
        $this->assertEquals(false, $this->cache->has("non_existant_item"));

        $this->assertEquals(true, $this->cache->hasTag("a_tag"));
        $this->assertEquals(true, $this->cache->hasTag("another_tag"));
        $this->assertEquals(false, $this->cache->hasTag("non_existant_tag"));

        $this->cache->deleteTag("another_tag");

        $this->assertEquals(true, $this->cache->has("int_item"));
        $this->assertEquals(false, $this->cache->has("float_item"));
        $this->assertEquals(false, $this->cache->has("non_existant_item"));

        $this->assertEquals(true, $this->cache->hasTag("a_tag"));
        $this->assertEquals(false, $this->cache->hasTag("another_tag"));
        $this->assertEquals(false, $this->cache->hasTag("non_existant_tag"));

        $this->cache->deleteTag("a_tag");

        $this->assertEquals(false, $this->cache->has("int_item"));
        $this->assertEquals(false, $this->cache->has("float_item"));
        $this->assertEquals(false, $this->cache->has("non_existant_item"));

        $this->assertEquals(false, $this->cache->hasTag("a_tag"));
        $this->assertEquals(false, $this->cache->hasTag("another_tag"));
        $this->assertEquals(false, $this->cache->hasTag("non_existant_tag"));
    }
}
