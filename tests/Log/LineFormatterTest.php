<?php

use PHPUnit\Framework\TestCase;
use StdCmp\Log\Formatters\Text;

class LineFormatterTest extends TestCase
{
    public function testDefaultFormat()
    {
        $record = [
            "priority" => 0,
            "priority_name" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["some" => "context"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];
        $formatter = new Text();

        $output = $formatter($record);

        $expected = '[1973-11-29 21:33:09]: emergency (0): Julie, do the thing ! {"some":"context"} 
';
        $this->assertEquals($expected, $output);
    }

    public function testCustomLineFormat()
    {
        $record = [
            "priority" => 0,
            "priority_name" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["some" => "context"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];
        $config = ["line_format" => "{datetime} {context.some} {notintherecord}"];
        $formatter = new Text($config);

        $output = $formatter($record);

        $expected = "1973-11-29 21:33:09 context {notintherecord}";
        $this->assertEquals($expected, $output);
    }

    public function testCustomLineAndDatetimeFormat()
    {
        $record = [
            "priority" => 0,
            "priority_name" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["some" => "context"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];
        $config = [
            "line_format" => "{datetime} {context.some} {notintherecord}",
            "datetime_format" => "H:i:s",
        ];
        $formatter = new Text($config);

        $output = $formatter($record);

        $expected = "21:33:09 context {notintherecord}";
        $this->assertEquals($expected, $output);
    }
}
