<?php

namespace Cache;

use function Sodium\crypto_aead_chacha20poly1305_encrypt;
use StdCmp\Cache\FileCache;
use PHPUnit\Framework\TestCase;
use StdCmp\Cache\CacheItem;

class FileTest extends TestCase
{
    /**
     * @var string
     */
    protected static $sDirPath = "/tmp/testStdCmp/";
    protected $dirPath;

    /**
     * @var FileCache
     */
    protected $cache;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->dirPath = self::$sDirPath;
        $this->cache = new FileCache($this->dirPath);
    }

    public static function setUpBeforeClass()
    {
        $cache = new FileCache(self::$sDirPath);
        $cache->deleteAll();
    }

    public static function tearDownAfterClass()
    {
        self::setUpBeforeClass();
    }

    public function testThatConstructorHandleDirPathArgument()
    {
        // path don't exists yet
        $cache = new FileCache($this->dirPath);

        $prop = new \ReflectionProperty($cache, "dirPath");
        $prop->setAccessible(true);

        $value = $prop->getValue($cache);
        $this->assertEquals($this->dirPath, $value);

        // path already exists
        $cache = new FileCache($this->dirPath);

        $value = $prop->getValue($cache);
        $this->assertEquals($this->dirPath, $value);

        // recursive path to create
        $cache = new FileCache($this->dirPath . "test1/test2/");

        $value = $prop->getValue($cache);
        $this->assertEquals($this->dirPath . "test1/test2/", $value);

        // path with dit directories
        $cache = new FileCache($this->dirPath . "test1/../test2");

        $value = $prop->getValue($cache);
        $this->assertEquals($this->dirPath . "test2/", $value);

        // throws exception
        $this->expectException(\Exception::class);
        new FileCache("/etc/StcCmp");
    }

    public function testThatConstructorHandleDefaultTTLArgument()
    {
        // no default TTL argument
        $cache = new FileCache($this->dirPath);

        $prop = new \ReflectionProperty(FileCache::class, "defaultTTL");
        $prop->setAccessible(true);

        $value = $prop->getValue($cache);
        $this->assertEquals(31536000, $value);

        // int
        $cache = new FileCache($this->dirPath, 12345);

        $value = $prop->getValue($cache);
        $this->assertEquals(12345, $value);

        // DateInterval
        $dt = new \DateInterval("PT123S");
        $cache = new FileCache($this->dirPath, $dt);

        $value = $prop->getValue($cache);
        $this->assertEquals(123, $value);
    }

    public function testSet()
    {
        $cache = new FileCache($this->dirPath);

        $cache->setValue("int", 123);
        $cache->setValue("float", 12.3);
        $cache->setValue("string", "a string");
        $cache->setValue("bool", true);
        $cache->setValue("callable", [$cache, "set"]);
        $cache->setValue("object", $cache);
        $cache->setValue("array", ["0", "one" => "one"]);

        $this->assertFileExists($this->dirPath . "int");
        $this->assertFileExists($this->dirPath . "float");
        $this->assertFileExists($this->dirPath . "string");
        $this->assertFileExists($this->dirPath . "bool");
        $this->assertFileExists($this->dirPath . "callable");
        $this->assertFileExists($this->dirPath . "object");
        $this->assertFileExists($this->dirPath . "array");
    }

    public function testSetWithTTL()
    {
        $cache = new FileCache($this->dirPath);

        $cache->setValue("int", 123, 123);
        $cache->setValue("float", 12.3, new \DateInterval("PT456S"));

        $value = filemtime($this->dirPath . "int");
        $this->assertEquals(time() + 123, $value);

        $value = filemtime($this->dirPath . "float");
        $this->assertEquals(time() + 456, $value);
    }

    public function testGet()
    {
        $cache = new FileCache($this->dirPath);

        $value = $cache->getValue("int");
        $this->assertEquals(123, $value);

        $value = $cache->getValue("float");
        $this->assertEquals(12.3, $value);

        $value = $cache->getValue("string");
        $this->assertEquals("a string", $value);

        $value = $cache->getValue("bool");
        $this->assertEquals(true, $value);

        $value = $cache->getValue("callable");
        $this->assertEquals([$cache, "set"], $value);

        $value = $cache->getValue("object");
        $this->assertEquals($cache, $value);

        $value = $cache->getValue("array");
        $this->assertEquals(["0", "one" => "one"], $value);

        $value = $cache->getValue("non_existant_key");
        $this->assertEquals(null, $value);

        // with default value
        $value = $cache->getValue("int", 456);
        $this->assertEquals(123, $value);

        $value = $cache->getValue("non_existant_key", 456);
        $this->assertEquals(456, $value);
    }

    public function testHas()
    {
        $cache = new FileCache($this->dirPath);

        $value = $cache->has("int");
        $this->assertEquals(true, $value);

        $value = $cache->has("not_existant_key");
        $this->assertEquals(false, $value);
    }

    public function testDelete()
    {
        $cache = new FileCache($this->dirPath);

        $this->assertFileExists($this->dirPath . "int");
        $cache->delete("int");
        $this->assertFileNotExists($this->dirPath . "int");

        $this->assertFileNotExists($this->dirPath . "non_existant_key");
        $cache->delete("non_existant_key");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");
    }

    public function testClear()
    {
        $cache = new FileCache($this->dirPath);

        // "int" deleted in tstDelete()
        $this->assertFileExists($this->dirPath . "float");
        $this->assertFileExists($this->dirPath . "string");
        $this->assertFileExists($this->dirPath . "bool");
        $this->assertFileExists($this->dirPath . "callable");
        $this->assertFileExists($this->dirPath . "object");
        $this->assertFileExists($this->dirPath . "array");

        $cache->deleteAll();

        $this->assertFileNotExists($this->dirPath . "int");
        $this->assertFileNotExists($this->dirPath . "float");
        $this->assertFileNotExists($this->dirPath . "string");
        $this->assertFileNotExists($this->dirPath . "bool");
        $this->assertFileNotExists($this->dirPath . "callable");
        $this->assertFileNotExists($this->dirPath . "object");
        $this->assertFileNotExists($this->dirPath . "array");

        // ensure empty
        $files =  [];
        $res = opendir($this->dirPath);
        while (($file = readdir($res)) !== false) {
            if ($file !== "." && $file !== "..") {
                $files[] = $$file;
            }
        }
        $this->assertEmpty($files);
    }

    public function testSetItem()
    {
        $this->cache->setItem(new CacheItem("item_int", 123));
        $this->cache->setItem(new CacheItem("item_float", 12.3));
        $this->cache->setItem(new CacheItem("item_string", "a string"));
        $this->cache->setItem(new CacheItem("item_bool", false));
        $this->cache->setItem(new CacheItem("item_callable", "FileTest::setUpBeforeClass"));
        $this->cache->setItem(new CacheItem("item_object", $this->cache));
        $this->cache->setItem(new CacheItem("item_array", ["zero", "one" => "one"]));

        $this->assertFileExists($this->dirPath . "item_int");
        $this->assertFileExists($this->dirPath . "item_float");
        $this->assertFileExists($this->dirPath . "item_string");
        $this->assertFileExists($this->dirPath . "item_bool");
        $this->assertFileExists($this->dirPath . "item_callable");
        $this->assertFileExists($this->dirPath . "item_object");
        $this->assertFileExists($this->dirPath . "item_array");

        $this->assertEquals(123, $this->cache->getValue("item_int"));
        $this->assertEquals(12.3, $this->cache->getValue("item_float"));
        $this->assertEquals("a string", $this->cache->getValue("item_string"));
        $this->assertEquals(false, $this->cache->getValue("item_bool"));
        $this->assertEquals("FileTest::setUpBeforeClass", $this->cache->getValue("item_callable"));
        $this->assertEquals($this->cache, $this->cache->getValue("item_object"));
        $this->assertEquals(["one" => "one", "zero"], $this->cache->getValue("item_array"));

        // now with expiration
        $this->cache->setItem(new CacheItem("item_int", 1234, 123)); // ttl
        $this->cache->setItem(new CacheItem("item_float", 12.34, new \DateTime("+ 456 seconds")));
        $this->cache->setItem(new CacheItem("item_string", "yet another value", new \DateTime("- 456 seconds"))); // time() - 456 seconds will actually set the expiration far in the future since it will be considered as a TTL and not a absolute TS

        $this->assertEquals(time() + 123, filemtime($this->dirPath . "item_int"));
        $this->assertEquals(time() + 456, filemtime($this->dirPath . "item_float"));
        $this->assertEquals(time() + (time() - 456), filemtime($this->dirPath . "item_string"));

        // manually set an old expiration date for "item_string"
        touch($this->dirPath . "item_string", time() - 456);
    }

    public function getItem()
    {
        $item = $this->cache->getItem("item_int");
        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertEquals("item_int", $item->getKey());
        $this->assertEquals(1234, $item->getValue());
        $this->assertEquals(true, $item->isHit());
        $this->assertEquals(time() + 123, $item->getExpiration());

        $item = $this->cache->getItem("item_float");
        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertEquals("item_float", $item->getKey());
        $this->assertEquals(12.34, $item->getValue());
        $this->assertEquals(12.34, $item->getValue(34.12)); // default value ignored
        $this->assertEquals(true, $item->isHit());
        $this->assertEquals(time() + 456, $item->getExpiration());

        // exists but expired
        $this->assertFileExists($this->dirPath . "item_string");

        $item = $this->cache->getItem("item_string");
        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertEquals("item_string", $item->getKey());
        $this->assertEquals(null, $item->getValue());
        $this->assertEquals(false, $item->isHit());
        $this->assertEquals(null, $item->getExpiration());

        // non existant
        $item = $this->cache->getItem("item_non_existant");
        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertEquals("item_non_existant", $item->getKey());
        $this->assertEquals(null, $item->getValue());
        $this->assertEquals(false, $item->isHit());
        $this->assertEquals(null, $item->getExpiration());
    }

    public function testGetItems()
    {
        $keys = [
            "item_int",
            "item_float",
            "item_string", // file exists but has expired
            "item_non_existant"
        ];

        $values = $this->cache->getItems($keys);

        $this->assertArrayHasKey("item_int", $values);
        $this->assertArrayHasKey("item_float", $values);
        $this->assertArrayHasKey("item_string", $values);
        $this->assertArrayHasKey("item_non_existant", $values);

        $this->assertEquals(1234, $values["item_int"]->getValue());
        $this->assertEquals(12.34, $values["item_float"]->getValue());
        $this->assertEquals(null, $values["item_string"]->getValue());
        $this->assertEquals("the default value", $values["item_string"]->getValue("the default value"));
        $this->assertEquals(null, $values["item_non_existant"]->getValue());
    }

    public function testSetValues()
    {
        $this->cache->deleteAll();

        $values = [
            "int_multiple" => 789,
            "float_multiple" => 789.456,
            "string_multiple" => "a multiple string",
        ];

        $this->assertFileNotExists($this->dirPath . "int_multiple");
        $this->assertFileNotExists($this->dirPath . "float_multiple");
        $this->assertFileNotExists($this->dirPath . "string_multiple");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");

        $this->cache->setValues($values);

        $this->assertFileExists($this->dirPath . "int_multiple");
        $this->assertFileExists($this->dirPath . "float_multiple");
        $this->assertFileExists($this->dirPath . "string_multiple");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");

    }

    public function testGetValues()
    {
        $keys = [
            "int_multiple",
            "float_multiple",
            "string_multiple",
            "non_existant_key"
        ];

        $values = $this->cache->getValues($keys);

        $this->assertArrayHasKey("int_multiple", $values);
        $this->assertArrayHasKey("float_multiple", $values);
        $this->assertArrayHasKey("string_multiple", $values);
        $this->assertArrayHasKey("non_existant_key", $values);

        $this->assertEquals(789, $values["int_multiple"]);
        $this->assertEquals(789.456, $values["float_multiple"]);
        $this->assertEquals("a multiple string", $values["string_multiple"]);
        $this->assertEquals(null, $values["non_existant_key"]);
    }

    public function testDeleteMultiple()
    {
        $keys = [
            "int_multiple",
            "float_multiple",
            "string_multiple",
            "non_existant_key"
        ];

        $this->assertFileExists($this->dirPath . "int_multiple");
        $this->assertFileExists($this->dirPath . "float_multiple");
        $this->assertFileExists($this->dirPath . "string_multiple");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");

        $values = $this->cache->deleteAll($keys);

        $this->assertFileNotExists($this->dirPath . "int_multiple");
        $this->assertFileNotExists($this->dirPath . "float_multiple");
        $this->assertFileNotExists($this->dirPath . "string_multiple");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");
    }

    public function testAddWithTag()
    {
        $this->cache->deleteAll();

        // setup
        $prop = new \ReflectionProperty(FileCache::class, "keysByTags");
        $prop->setAccessible(true);

        $keysByTags = $prop->getValue($this->cache);
        $this->assertEmpty($keysByTags);

        // with one tag
        $item = new CacheItem("int", 123);
        $item->addTag("a_tag");

        $this->cache->setItem($item);

        $keysByTags = $prop->getValue($this->cache);
        $this->assertArrayHasKey("a_tag", $keysByTags);
        $this->assertArrayHasKey("int", $keysByTags["a_tag"]);

        // with several tags
        $item = new CacheItem("float", 12.3);
        $item->setTags(["a_tag", "another_tag"]);

        $this->cache->setItem($item);

        $keysByTags = $prop->getValue($this->cache);
        $this->assertArrayHasKey("a_tag", $keysByTags);
        $this->assertArrayHasKey("another_tag", $keysByTags);
        $this->assertArrayHasKey("int", $keysByTags["a_tag"]);
        $this->assertArrayHasKey("float", $keysByTags["a_tag"]);
        $this->assertArrayHasKey("float", $keysByTags["another_tag"]);

        $this->assertFileExists($this->dirPath . "filecache.tags");
    }

    public function testHasTag()
    {
        $this->assertEquals(true, $this->cache->hasTag("a_tag"));
        $this->assertEquals(true, $this->cache->hasTag("another_tag"));
        $this->assertEquals(false, $this->cache->hasTag("non_existant_tag"));
    }

    public function testGetTagsForKey()
    {
        $method = new \ReflectionMethod(FileCache::class, "getTagsForKey");
        $method->setAccessible(true);

        $tags = $method->invoke($this->cache, "non_existant_key");
        $this->assertEmpty($tags);

        $tags = $method->invoke($this->cache, "int");
        $this->assertEquals(1, count($tags));
        $this->assertContains("a_tag", $tags);

        $tags = $method->invoke($this->cache, "float");
        $this->assertEquals(2, count($tags));
        $this->assertContains("a_tag", $tags);
        $this->assertContains("another_tag", $tags);
    }

    public function testGetWithTag()
    {
        // 1 items
        $items = $this->cache->getItemsWithTag("another_tag");
        $this->assertEquals(1, count($items));
        $this->assertArrayHasKey("float", $items);

        $floatItem = $this->cache->getItem("float");
        $tags = $floatItem->getTags();
        $this->assertEquals(2, count($tags));
        $this->assertContains("a_tag", $tags);
        $this->assertContains("another_tag", $tags);

        $this->assertEquals($floatItem, $items["float"]);

        // 2 items
        $items = $this->cache->getItemsWithTag("a_tag");
        $this->assertEquals(2, count($items));
        $this->assertArrayHasKey("int", $items);
        $this->assertArrayHasKey("float", $items);

        $intItem = $this->cache->getItem("int");
        $tags = $intItem->getTags();
        $this->assertEquals(1, count($tags));
        $this->assertContains("a_tag", $tags);
        $this->assertNotContains("another_tag", $tags);

        $this->assertEquals($intItem, $items["int"]);
        $this->assertEquals($floatItem, $items["float"]);
    }
}
