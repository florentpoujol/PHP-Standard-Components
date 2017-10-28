<?php

use StdCmp\Log\Helpers;
use PHPUnit\Framework\TestCase;

class MessagePlaceholdersHelpersTest extends TestCase
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

        $proc = new Helpers\MessagePlaceholders();
        $record = $proc($record);

        $this->assertEquals($expected, $record);
    }
}
