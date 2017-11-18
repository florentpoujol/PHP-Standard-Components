<?php

use PHPUnit\Framework\TestCase;
use StdCmp\Log\Formatters\Text;

class TextFormatterTest extends TestCase
{
    public function testDefaultFormat()
    {
        $record = [
            "level" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["some" => "context"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];
        $formatter = new Text();

        $output = $formatter($record);
        $expected = '123456789: emergency: Julie, do the thing ! {"some":"context"}
';
        $this->assertEquals($expected, $output);
    }

    public function testCustomLineFormat()
    {
        $record = [
            "level" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["thing" => "stuff"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];

        $formatter = new Text("{timestamp} \n{context.thing} \n{notintherecord}");
        $output = $formatter($record);

        $expected = "123456789 
stuff 
{notintherecord}";
        $this->assertEquals($expected, $output);
    }
}
