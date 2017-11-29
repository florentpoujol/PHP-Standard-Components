<?php

namespace Console;

use PHPUnit\Framework\TestCase;
use StdCmp\Console\Command;

class MyAppCommandTest extends TestCase
{
    function testSubCommands()
    {
        $output = `php myapp.php subCmd:class`;
        $expected = "stuff done !";
        $this->assertSame($expected, $output);

        $output = `php myapp.php subCmd:callable`;
        $expected = "Static stuff done !";
        $this->assertSame($expected, $output);

        $output = `php myapp.php subCmd:closure`;
        $expected = "Closure called\n";
        $this->assertSame($expected, $output);
    }
}
