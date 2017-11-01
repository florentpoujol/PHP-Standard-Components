<?php

namespace Cache;

use StdCmp\Cache\File;
use PHPUnit\Framework\TestCase;
use StdCmp\Cache\Item;

class FileTest extends TestCase
{
    protected static $sDirPath = "/tmp/testStdCmp/";
    protected $dirPath;
    /**
     * @var File
     */
    protected $cache;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->dirPath = self::$sDirPath;
        $this->cache = new File($this->dirPath);
    }

    public static function setUpBeforeClass()
    {
        $cache = new File(self::$sDirPath);
        $cache->clear();
    }

    public static function tearDownAfterClass()
    {
        self::setUpBeforeClass();
    }

    public function testThatConstructorHandleDirPathArgument()
    {
        // path don't exists yet
        $cache = new File($this->dirPath);

        $prop = new \ReflectionProperty($cache, "dirPath");
        $prop->setAccessible(true);

        $value = $prop->getValue($cache);
        $this->assertEquals($this->dirPath, $value);

        // path already exists
        $cache = new File($this->dirPath);

        $value = $prop->getValue($cache);
        $this->assertEquals($this->dirPath, $value);

        // recursive path to create
        $cache = new File($this->dirPath . "test1/test2/");

        $value = $prop->getValue($cache);
        $this->assertEquals($this->dirPath . "test1/test2/", $value);

        // path with dit directories
        $cache = new File($this->dirPath . "test1/../test2");

        $value = $prop->getValue($cache);
        $this->assertEquals($this->dirPath . "test2/", $value);

        // throws exception
        $this->expectException(\Exception::class);
        new File("/etc/StcCmp");
    }

    public function testThatConstructorHandleDefaultTTLArgument()
    {
        // no default TTL argument
        $cache = new File($this->dirPath);

        $prop = new \ReflectionProperty(File::class, "defaultTTL");
        $prop->setAccessible(true);

        $value = $prop->getValue($cache);
        $this->assertEquals(31536000, $value);

        // int
        $cache = new File($this->dirPath, 12345);

        $value = $prop->getValue($cache);
        $this->assertEquals(12345, $value);

        // DateInterval
        $dt = new \DateInterval("PT123S");
        $cache = new File($this->dirPath, $dt);

        $value = $prop->getValue($cache);
        $this->assertEquals(123, $value);
    }

    public function testSet()
    {
        $cache = new File($this->dirPath);

        $cache->set("int", 123);
        $cache->set("float", 12.3);
        $cache->set("string", "a string");
        $cache->set("bool", true);
        $cache->set("callable", [$cache, "set"]);
        $cache->set("object", $cache);
        $cache->set("array", ["0", "one" => "one"]);

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
        $cache = new File($this->dirPath);

        $cache->set("int", 123, 123);
        $cache->set("float", 12.3, new \DateInterval("PT456S"));

        $value = filemtime($this->dirPath . "int");
        $this->assertEquals(time() + 123, $value);

        $value = filemtime($this->dirPath . "float");
        $this->assertEquals(time() + 456, $value);
    }

    public function testGet()
    {
        $cache = new File($this->dirPath);

        $value = $cache->get("int");
        $this->assertEquals(123, $value);

        $value = $cache->get("float");
        $this->assertEquals(12.3, $value);

        $value = $cache->get("string");
        $this->assertEquals("a string", $value);

        $value = $cache->get("bool");
        $this->assertEquals(true, $value);

        $value = $cache->get("callable");
        $this->assertEquals([$cache, "set"], $value);

        $value = $cache->get("object");
        $this->assertEquals($cache, $value);

        $value = $cache->get("array");
        $this->assertEquals(["0", "one" => "one"], $value);

        $value = $cache->get("non_existant_key");
        $this->assertEquals(null, $value);

        // with default value
        $value = $cache->get("int", 456);
        $this->assertEquals(123, $value);

        $value = $cache->get("non_existant_key", 456);
        $this->assertEquals(456, $value);
    }

    public function testHas()
    {
        $cache = new File($this->dirPath);

        $value = $cache->has("int");
        $this->assertEquals(true, $value);

        $value = $cache->has("not_existant_key");
        $this->assertEquals(false, $value);
    }

    public function testDelete()
    {
        $cache = new File($this->dirPath);

        $this->assertFileExists($this->dirPath . "int");
        $cache->delete("int");
        $this->assertFileNotExists($this->dirPath . "int");

        $this->assertFileNotExists($this->dirPath . "non_existant_key");
        $cache->delete("non_existant_key");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");
    }

    public function testClear()
    {
        $cache = new File($this->dirPath);

        // "int" deleted in tstDelete()
        $this->assertFileExists($this->dirPath . "float");
        $this->assertFileExists($this->dirPath . "string");
        $this->assertFileExists($this->dirPath . "bool");
        $this->assertFileExists($this->dirPath . "callable");
        $this->assertFileExists($this->dirPath . "object");
        $this->assertFileExists($this->dirPath . "array");

        $cache->clear();

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
        $this->cache->save(new Item("item_int", 123));
        $this->cache->save(new Item("item_float", 12.3));
        $this->cache->save(new Item("item_string", "a string"));
        $this->cache->save(new Item("item_bool", false));
        $this->cache->save(new Item("item_callable", "FileTest::setUpBeforeClass"));
        $this->cache->save(new Item("item_object", $this->cache));
        $this->cache->save(new Item("item_array", ["zero", "one" => "one"]));

        $this->assertFileExists($this->dirPath . "item_int");
        $this->assertFileExists($this->dirPath . "item_float");
        $this->assertFileExists($this->dirPath . "item_string");
        $this->assertFileExists($this->dirPath . "item_bool");
        $this->assertFileExists($this->dirPath . "item_callable");
        $this->assertFileExists($this->dirPath . "item_object");
        $this->assertFileExists($this->dirPath . "item_array");

        $this->assertEquals(123, $this->cache->get("item_int"));
        $this->assertEquals(12.3, $this->cache->get("item_float"));
        $this->assertEquals("a string", $this->cache->get("item_string"));
        $this->assertEquals(false, $this->cache->get("item_bool"));
        $this->assertEquals("FileTest::setUpBeforeClass", $this->cache->get("item_callable"));
        $this->assertEquals($this->cache, $this->cache->get("item_object"));
        $this->assertEquals(["one" => "one", "zero"], $this->cache->get("item_array"));

        // now with expiration
        $this->cache->save(new Item("item_int", 1234, 123)); // ttl
        $this->cache->save(new Item("item_float", 12.34, new \DateTime("+ 456 seconds")));
        $this->cache->save(new Item("item_string", "yet another value", new \DateTime("- 456 seconds"))); // - 456 seconds to set the expiration in the past

        $this->assertEquals(time() + 123, filemtime($this->dirPath . "item_int"));
        $this->assertEquals(time() + 456, filemtime($this->dirPath . "item_float"));
    }

    public function getItem()
    {
        $item = $this->cache->getItem("item_int");
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals("item_int", $item->getKey());
        $this->assertEquals(1234, $item->get());
        $this->assertEquals(true, $item->isHit());
        $this->assertEquals(time() + 123, $item->expireAt()->getTimestamp());

        $item = $this->cache->getItem("item_float");
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals("item_float", $item->getKey());
        $this->assertEquals(12.34, $item->get());
        $this->assertEquals(12.34, $item->get(34.12)); // default value ignored
        $this->assertEquals(true, $item->isHit());
        $this->assertEquals(time() + 456, $item->expireAt()->getTimestamp());

        // exists but expired
        $this->assertFileExists($this->dirPath . "item_string");

        $item = $this->cache->getItem("item_string");
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals("item_string", $item->getKey());
        $this->assertEquals(null, $item->get());
        $this->assertEquals(false, $item->isHit());
        $this->assertEquals(null, $item->expireAt());

        // non existant
        $item = $this->cache->getItem("item_non_existant");
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals("item_non_existant", $item->getKey());
        $this->assertEquals(null, $item->get());
        $this->assertEquals(false, $item->isHit());
        $this->assertEquals(null, $item->expireAt());
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

        $this->assertEquals(1234, $values["item_int"]->get());
        $this->assertEquals(12.34, $values["item_float"]->get());
        $this->assertEquals(null, $values["item_string"]->get());
        $this->assertEquals("the default value", $values["item_string"]->get("the default value"));
        $this->assertEquals(null, $values["item_non_existant"]->get());
    }

    public function testSaveDefered()
    {
        $this->cache->saveDeferred(new Item("itemdef_int", 12345));
        $this->cache->saveDeferred(new Item("itemdef_float", 12.345));
        $this->cache->saveDeferred(new Item("itemdef_string", "a defered string"));
        $this->cache->saveDeferred(new Item("itemdef_bool", true));
        $this->cache->saveDeferred(new Item("itemdef_callable", "FileTest::setUpBeforeClass"));
        $this->cache->saveDeferred(new Item("itemdef_object", new Item("key", "value")));
        $this->cache->saveDeferred(new Item("itemdef_array", ["zero", "one" => "defered"]));

        $this->assertFileNotExists($this->dirPath . "itemdef_int");
        $this->assertFileNotExists($this->dirPath . "itemdef_float");
        $this->assertFileNotExists($this->dirPath . "itemdef_string");
        $this->assertFileNotExists($this->dirPath . "itemdef_bool");
        $this->assertFileNotExists($this->dirPath . "itemdef_callable");
        $this->assertFileNotExists($this->dirPath . "itemdef_object");
        $this->assertFileNotExists($this->dirPath . "itemdef_array");

        $this->cache->commit();

        $this->assertFileExists($this->dirPath . "itemdef_int");
        $this->assertFileExists($this->dirPath . "itemdef_float");
        $this->assertFileExists($this->dirPath . "itemdef_string");
        $this->assertFileExists($this->dirPath . "itemdef_bool");
        $this->assertFileExists($this->dirPath . "itemdef_callable");
        $this->assertFileExists($this->dirPath . "itemdef_object");
        $this->assertFileExists($this->dirPath . "itemdef_array");

        $this->assertEquals(12345, $this->cache->get("itemdef_int"));
        $this->assertEquals(12.345, $this->cache->get("itemdef_float"));
        $this->assertEquals("a defered string", $this->cache->get("itemdef_string"));
        $this->assertEquals(true, $this->cache->get("itemdef_bool"));
        $this->assertEquals("FileTest::setUpBeforeClass", $this->cache->get("itemdef_callable"));
        $this->assertEquals(new Item("key", "value"), $this->cache->get("itemdef_object"));
        $this->assertEquals(["one" => "defered", "zero"], $this->cache->get("itemdef_array"));
    }

    public function testSetMultiple()
    {
        $this->cache->clear();

        $values = [
            "int_multiple" => 789,
            "float_multiple" => 789.456,
            "string_multiple" => "a multiple string",
        ];

        $this->assertFileNotExists($this->dirPath . "int_multiple");
        $this->assertFileNotExists($this->dirPath . "float_multiple");
        $this->assertFileNotExists($this->dirPath . "string_multiple");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");

        $this->cache->setMultiple($values);

        $this->assertFileExists($this->dirPath . "int_multiple");
        $this->assertFileExists($this->dirPath . "float_multiple");
        $this->assertFileExists($this->dirPath . "string_multiple");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");

    }

    public function testGetMultiple()
    {
        $keys = [
            "int_multiple",
            "float_multiple",
            "string_multiple",
            "non_existant_key"
        ];

        $values = $this->cache->getMultiple($keys);

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

        $values = $this->cache->deleteMultiple($keys);

        $this->assertFileNotExists($this->dirPath . "int_multiple");
        $this->assertFileNotExists($this->dirPath . "float_multiple");
        $this->assertFileNotExists($this->dirPath . "string_multiple");
        $this->assertFileNotExists($this->dirPath . "non_existant_key");
    }


}
