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

        $input = "{placeholder} {place.holder} {tostringobject} {array} %{emptyarray}% {notinreplacement}";
        $replacements = [
            "placeholder" => "thevalue",
            "place" => [
                "holder" => "thenestedvalue",
            ],
            "tostringobject" => $toStringObject,
            "emptyarray" => [],
        ];
        $replacements["array"] = $replacements["place"];

        $output = $this->replacePlaceholders($input, $replacements);

        $this->assertContains("thevalue", $output);
        $this->assertContains("thenestedvalue", $output);
        $this->assertContains("the tostring object", $output);
        $this->assertContains("%%", $output); // {emptyarray} replaced by ""
        $this->assertContains("{notinreplacement}", $output);

        $json = '{"holder":"thenestedvalue"}';
        $this->assertContains($json, $output);
    }

    public function testExceptionOnNonCastableObjects()
    {
        $object = new class
        {
            public $property = "prop";
        };

        $input = "{object}";
        $replacements = [
            "object" => $object,
        ];

        $this->expectException(UnexpectedValueException::class);

        $this->replacePlaceholders($input, $replacements);
    }
}
