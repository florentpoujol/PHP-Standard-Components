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
        $infos = $formatter($record);

        $expectedQuery = "(priority, priority_name, message, context, timestamp, extra) "
            . "VALUES (:priority, :priority_name, :message, :context, :timestamp, :extra)";
        $this->assertEquals($expectedQuery, $infos["query"]);

        $data = $record;
        $data["context"] = '{"some":"context"}';
        $data["extra"] = '[]';
        $this->assertEquals($data, $infos["data"]);
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
        $infos = $formatter($record);

        $expectedQuery = "(priority, thecontext) VALUES (:priority, :thecontext)";
        $this->assertEquals($expectedQuery, $infos["query"]);

        $data = [];
        $data["priority"] = "emergency";
        $data["thecontext"] = "context";
        $this->assertEquals($data, $infos["data"]);
    }
}
