<?php

use StdCmp\Log\Processors;
use PHPUnit\Framework\TestCase;

class MessagePlaceholdersProcessorTest extends TestCase
{
    public function testMessagePlaceholdersReplacements()
    {
        $record = [
            "message" => "User {user.name} has {done} {action}",
            "context" => [
                "user" => [
                    "name" => "Florent",
                ],
                "action" => "stuff",
            ],
        ];

        $expected = $record;
        $expected["message"] = "User Florent has {done} stuff";

        $proc = new Processors\MessagePlaceholders();
        $record = $proc($record);

        $this->assertEquals($expected, $record);
    }
}
