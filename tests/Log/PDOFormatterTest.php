<?php

use StdCmp\Log\Formatters;
use PHPUnit\Framework\TestCase;

class PDOFormatterTest extends TestCase
{
    public function testWithoutMap()
    {
        $record = [
            "priority" => 0,
            "priority_name" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["some" => "context"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];

        $formatter = new Formatters\PDO();
        $query = $formatter($record);

        $expectedStmt = "(priority, priority_name, message, context, timestamp, extra) "
            . "VALUES (:priority, :priority_name, :message, :context, :timestamp, :extra)";
        $this->assertEquals($expectedStmt, $query["statement"]);

        $record["context"] = '{"some":"context"}';
        $record["extra"] = '[]';
        $this->assertEquals($record, $query["params"]);
    }

    public function testWithMap()
    {
        $record = [
            "priority" => 0,
            "priority_name" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["some" => "context"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];

        $map = [
            // DB field => record field
            "priority" => "priority_name",
            "thecontext" => "context.some"
        ];

        $formatter = new Formatters\PDO($map);
        $query = $formatter($record);

        $expectedStmt = "(priority, thecontext) VALUES (:priority, :thecontext)";
        $this->assertEquals($expectedStmt, $query["statement"]);

        $params = [
            "priority" => "emergency",
            "thecontext" => "context",
        ];
        $this->assertEquals($params, $query["params"]);
    }
}
