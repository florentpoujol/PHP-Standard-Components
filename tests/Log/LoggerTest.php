<?php

use StdCmp\Log\Logger;
use PHPUnit\Framework\TestCase;

function namedFunction(array $record)
{
    $record["namedFunction"] = true;
    return $record;
}

class LoggerTest extends TestCase
{
    public function __invoke($record)
    {
        $record["__invoke"] = true;
        return $record;
    }

    public function method($record)
    {
        $record["method"] = true;
        return $record;
    }

    public static function staticMethod($record)
    {
        $record["staticMethod"] = true;
        return $record;
    }

    public static $record;

    public static function otherStaticMethod($record)
    {
        $record["otherStaticMethod"] = true;
        self::$record = $record;
        return $record;
    }

    public function testAllKindOfCallable()
    {
        $logger = new Logger();
        $logger->addWriter(function(){});

        $logger->addHelper("namedFunction");
        $logger->addHelper(function($record) {
            $record["anonymousFunction"] = true;
            return $record;
        });
        $logger->addHelper($this);
        $logger->addHelper([$this, "method"]);
        $logger->addHelper(["LoggerTest", "staticMethod"]);
        $logger->addHelper("LoggerTest::otherStaticMethod"); // save the record

        $logger->debug("all callable");

        $this->assertArrayHasKey("namedFunction", self::$record);
        $this->assertArrayHasKey("anonymousFunction", self::$record);
        $this->assertArrayHasKey("__invoke", self::$record);
        $this->assertArrayHasKey("method", self::$record);
        $this->assertArrayHasKey("staticMethod", self::$record);
        $this->assertArrayHasKey("otherStaticMethod", self::$record);
    }
}
