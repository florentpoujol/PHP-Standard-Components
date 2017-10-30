<?php

namespace Cache;

use StdCmp\Cache\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    protected static $sDirPath = "/tmp/testStdCmp/";
    protected $dirPath;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->dirPath = self::$sDirPath;
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

}
