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
        $success = $this->cache->setValue("int", 123, 123);
        $this->assertEquals(true, $success);

        $success = $this->cache->setValue("float", 12.3, 123);
        $this->assertEquals(true, $success);

        // without expiration
        $success = $this->cache->setValue("bool", true);
        $this->assertEquals(true, $success);

        $success = $this->cache->setValue("string", "a string");
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
        $value = $this->cache->getValue("int");
        $this->assertEquals(123, $value);

        $value = $this->cache->getValue("float");
        $this->assertEquals(null, $value); // expired

        $value = $this->cache->getValue("bool");
        $this->assertEquals(true, $value);

        $value = $this->cache->getValue("string");
        $this->assertEquals("a string", $value);

        $value = $this->cache->getValue("non_existant_key");
        $this->assertEquals(null, $value);
    }

    public function testOverrideValue()
    {
        $this->assertEquals(false, $this->cache->has("float"));
        $this->assertEquals(null, $this->cache->getValue("float"));

        // override value and reset the expiration time
        $value = $this->cache->setValue("float", 1.23);
        $this->assertEquals(true, $value);
        $this->assertEquals(1.23, $this->cache->getValue("float"));
        $this->assertEquals(1.23, $this->cache->getValue("float"));
    }

    public function testDeleteMultiple()
    {
        $this->cache->setValue("int", 123);
        $this->cache->setValue("int2", 123);

        $this->assertEquals(true, $this->cache->has("int"));
        $this->assertEquals(true, $this->cache->has("int2"));
        $this->assertEquals(true, $this->cache->has("bool"));

        $value = $this->cache->deleteAll(["int", "int2", "bool"]);

        $this->assertEquals(false, $this->cache->has("int"));
        $this->assertEquals(false, $this->cache->has("int2"));
        $this->assertEquals(false, $this->cache->has("bool"));
    }

    public function testDeleteAll()
    {
        $this->cache->setValue("int", 123);
        $this->cache->setValue("int2", 123);
        $this->cache->setValue("float1", 12.3);

        $this->assertEquals(true, $this->cache->has("int"));
        $this->assertEquals(true, $this->cache->has("int2"));
        $this->assertEquals(true, $this->cache->has("float1"));

        $this->cache->deleteAll();

        $this->assertEquals(false, $this->cache->has("int"));
        $this->assertEquals(false, $this->cache->has("int2"));
        $this->assertEquals(false, $this->cache->has("float1"));

        $query = $this->pdo->query("SELECT * FROM pdo_cache");
        $query->execute();
        $entries = $query->fetchAll();
        $this->assertEquals(0, count($entries));
    }

    public function testSetValues()
    {
        $this->cache->deleteAll();

        $this->assertEquals(false, $this->cache->has("int"));
        $this->assertEquals(false, $this->cache->has("float"));
        $this->assertEquals(false, $this->cache->has("string"));
        $this->assertEquals(false, $this->cache->has("bool"));

        $values = [
            "int" => 456,
            "float" => 456.789,
        ];
        $this->cache->setValues($values);

        $values = [
            "string" => "another string",
            "bool" => false,
        ];
        $this->cache->setValues($values, 123);

        $this->assertEquals(true, $this->cache->has("int"));
        $this->assertEquals(true, $this->cache->has("float"));
        $this->assertEquals(true, $this->cache->has("string"));
        $this->assertEquals(true, $this->cache->has("bool"));
    }

    function testGetValues()
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
        $values = $this->cache->getValues($keys, "default value");

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
}
