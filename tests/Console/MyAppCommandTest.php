<?php

namespace Console;

use PHPUnit\Framework\TestCase;
use StdCmp\Console\Command;

class MyAppCommandTest extends TestCase
{
    function testSubCommands()
    {
        $output = shell_exec('php ' . __dir__ . '/myapp.php subCmd:class');
        $expected = "stuff done !";
        $this->assertSame($expected, $output);

        $output = shell_exec('php ' . __dir__ . '/myapp.php subCmd:callable');
        $expected = "Static stuff done !";
        $this->assertSame($expected, $output);

        $output = shell_exec('php ' . __dir__ . '/myapp.php subCmd:closure');
        $expected = "Closure called\n";
        $this->assertSame($expected, $output);
    }
}
