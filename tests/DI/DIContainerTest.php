<?php

namespace DI;

use StdCmp\DI\DIContainer;
use PHPUnit\Framework\TestCase;

require "classes.php";

class DIContainerTest extends TestCase
{
    protected static $scontainer;
    /**
     * @var DIContainer
     */
    protected $container;

    public static function setUpBeforeClass()
    {
        self::$scontainer = new DIContainer();
    }

    protected function setUp()
    {
        $this->container = self::$scontainer;
    }

    function testSetParameters()
    {
        $this->container->setParameter("string", "a string");
        $this->container->setParameter("int", 123);
        $this->container->setParameter("float", 123.456);
        $this->container->setParameter("bool", true);
        $this->container->setParameter("array", ["a", "array"]);

        $class = new \ReflectionClass(DIContainer::class);
        $prop = $class->getProperty("parameters");
        $prop->setAccessible(true);
        $params = $prop->getValue($this->container);

        $this->assertEquals("a string", $params["string"]);
        $this->assertEquals(123, $params["int"]);
        $this->assertEquals(123.456, $params["float"]);
        $this->assertEquals(true, $params["bool"]);
        $this->assertEquals(["a", "array"], $params["array"]);
    }

    function testGetParameter()
    {
        $value = $this->container->getParameter("string");
        $this->assertEquals("a string", $value);
        $value = $this->container->getParameter("int");
        $this->assertEquals(123, $value);
        $value = $this->container->getParameter("float");
        $this->assertEquals(123.456, $value);
        $value = $this->container->getParameter("bool");
        $this->assertEquals(true, $value);
        $value = $this->container->getParameter("array");
        $this->assertEquals(["a", "array"], $value);
        $value = $this->container->getParameter("non_existant_key");
        $this->assertEquals(null, $value);
    }

    function testSetService()
    {

        $this->container->set("logger", MonoLogger::class);
        $this->container->set(LoggerInterface::class, MonoLogger::class);
        $this->container->set(OnlyParams::class, [
            "string" => "a simple string", // simple value, 4th parameter
            "priority" => "%int", // int parameter
            "monoLogger" => "@logger", // logger service
        ]);
        $id = 0;
        $this->container->set("callable", function($c) use (&$id) {
            $this->assertInstanceOf(DIContainer::class, $c);
            $id++;
            return $id;
        });

        $class = new \ReflectionClass(DIContainer::class);
        $prop = $class->getProperty("services");
        $prop->setAccessible(true);
        $services = $prop->getValue($this->container);

        $this->assertEquals(MonoLogger::class, $services["logger"]);
        $this->assertEquals(MonoLogger::class, $services[LoggerInterface::class]);
        $this->assertEquals([
            "string" => "a simple string", // simple value, 4th parameter
            "priority" => "%int", // int parameter
            "monoLogger" => "@logger", // logger service
        ], $services[OnlyParams::class]);
        $this->assertArrayHasKey("callable", $services);
        $this->assertInternalType("callable", $services["callable"]);
    }

    function testHas()
    {
        $this->assertEquals(true, $this->container->has("logger"));
        $this->assertEquals(true, $this->container->has(LoggerInterface::class));
        $this->assertEquals(true, $this->container->has(OnlyParams::class));
        $this->assertEquals(true, $this->container->has("callable"));
        $this->assertEquals(false, $this->container->has("non_existant_key"));
    }

    function testGetServiceFromCallable()
    {
        $value = $this->container->get("callable");
        $this->assertEquals(1, $value);
        $value = $this->container->get("callable");
        $this->assertEquals(1, $value);

        $value = $this->container->make("callable");
        $this->assertEquals(2, $value);
        $value = $this->container->make("callable");
        $this->assertEquals(3, $value);

        $value = $this->container->get("callable");
        $this->assertEquals(1, $value);

        $value = $this->container->get("non_existant_key");
        $this->assertEquals(null, $value);


        $class = new \ReflectionClass(DIContainer::class);

        $prop = $class->getProperty("services");
        $prop->setAccessible(true);
        $services = $prop->getValue($this->container);

        $this->assertEquals(1, $services["callable"]);

        $prop = $class->getProperty("factories");
        $prop->setAccessible(true);
        $factories = $prop->getValue($this->container);

        $this->assertInternalType("callable", $factories["callable"]);
    }

    function testSimpleAutowire()
    {
        $object = $this->container->get(SimpleAutowire::class);

        $this->assertInstanceOf(SimpleAutowire::class, $object);
        $this->assertInstanceOf(LoggerInterface::class, $object->logger);
        $this->assertInstanceOf(MonoLogger::class, $object->logger);
    }

    function testCreateObjectFromOnlyParameters()
    {
        $object = $this->container->get(OnlyParams::class);

        $this->assertInstanceOf(OnlyParams::class, $object);
        $this->assertEquals("a simple string", $object->string);
        $this->assertEquals(123, $object->priority);
        $this->assertInstanceOf(MonoLogger::class, $object->monoLogger);
    }

    function testCreateObjectFromAutowirePlusParams()
    {
        $this->container->set(AutowirePlusParams::class, [
            "monoLogger" => "@logger", // logger service, 3rd param
            "string" => "a simple string", // simple value, 4th parameter
            "priority" => "%int", // int parameter, 1st param
            // 2nd param is OtherLogger and autowired
        ]);
        $object = $this->container->get(AutowirePlusParams::class);

        $this->assertInstanceOf(AutowirePlusParams::class, $object);

        $this->assertInstanceOf(LoggerInterface::class, $object->logger);
        $this->assertInstanceOf(OtherLogger::class, $object->logger);

        $this->assertInstanceOf(LoggerInterface::class, $object->monoLogger);
        $this->assertInstanceOf(MonoLogger::class, $object->monoLogger);

        $this->assertEquals("a simple string", $object->string);
        $this->assertEquals(123, $object->priority);
    }

    function testGetThrowsExceptionOnUnresolvedName()
    {
        $this->container->set("unknown_service", "not a class");
        $this->container->set("unknown_alias", "unknown_service");

        $this->expectException(\Exception::class);

        $this->container->get("unknown_service");
        $this->container->get("unknown_alias");
    }

    function testAutowireExceptions()
    {
        $container = new DIContainer();

        $this->expectException(\Exception::class);
        $container->get(SimpleAutowire::class); // interface without alias
        $container->get(OnlyParams::class); // unknown scalar parameters
        $container->get(AutowirePlusParams::class); // same
    }
}
