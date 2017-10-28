<?php

use PHPUnit\Framework\TestCase;

class PlaceholderReplacementTraitTest extends TestCase
{
    use \StdCmp\Log\Traits\PlaceholderReplacement;

    public function testPlaceholderReplacement()
    {
        $toStringObject = new class
        {
            public function __tostring()
            {
                return "the tostring object";
            }
        };

        $object = new class
        {
            public $property = "prop";
        }; // will be casted to array

        $input = "{placeholder} {place.holder} {tostringobject} {object} {array} %{emptyarray}% {notinreplacement}";
        $replacements = [
            "placeholder" => "thevalue",
            "place" => [
                "holder" => "thenestedvalue",
            ],
            "tostringobject" => $toStringObject,
            "object" => $object,
            "emptyarray" => [],
        ];
        $replacements["array"] = $replacements["place"];

        $output = $this->replacePlaceholders($input, $replacements);

        $this->assertContains("thevalue", $output);
        $this->assertContains("thenestedvalue", $output);
        $this->assertContains("the tostring object", $output);
        $this->assertContains('{"property":"prop"}', $output); // object casted to array

        $this->assertContains("%%", $output); // {emptyarray} replaced by ""
        $this->assertContains("{notinreplacement}", $output);

        $json = '{"holder":"thenestedvalue"}';
        $this->assertContains($json, $output);
    }
}
