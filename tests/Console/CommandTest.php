<?php

namespace Console;

use StdCmp\Console\Command;
use PHPUnit\Framework\TestCase;

class MyApp extends Command
{
    function getConfig(): array
    {
        $myconfig = [
            "argumentNames" => ["arg1", "arg2"],
        ];
        $this->config = array_merge($this->config, $myconfig);
        return $this->config;
    }
}

class CommandTest extends TestCase
{
    function testGetArguments()
    {
        $_SERVER["argv"] = ["filename.php", "firstArg", "-o", "secondArg", "--option=value"];
        $cmd = new Command();

        $args = $cmd->getArguments();
        $this->assertSame(["firstArg", "secondArg"], $args);
    }

    function testGetArgument()
    {
        $_SERVER["argv"] = ["filename.php", "firstArg", "-o", "secondArg", "--option=value"];
        $cmd = new Command();
        $config = $cmd->getConfig();
        $config["argumentNames"] = ["arg1", "arg2"];
        $cmd->setConfig($config);

        $arg = $cmd->getArgument("arg1");
        $this->assertSame("firstArg", $arg);

        $arg = $cmd->getArgument("arg2");
        $this->assertSame("secondArg", $arg);

        $arg = $cmd->getArgument("non_existant");
        $this->assertSame(null, $arg);
    }

    function testHasShortOptions()
    {
        $output = `php mycommand.php --testname=hasShortOption -a -b=b -c="c" -d d 2>&1`;
        $expected = "1-1-1-1--";
        $this->assertSame($expected, $output);
    }

    function testHasLongOptions()
    {
        $stuff = "";
        $output = `php mycommand.php --testname=hasLongOption --aa --bb=bb --cc="cc" --dd "dd" --ee="ee" 2>&1`;
        $expected = "1-1-1-1--";
        $this->assertSame($expected, $output);
    }

    function testGetShortOptions()
    {
        $output = `php mycommand.php --testname=getShortOption -a -b=b -c="c" -d d 2>&1`;
        $expected = "default-b-c-d--";
        $this->assertSame($expected, $output);
    }

    function testGetLongOptions()
    {
        $output = `php mycommand.php --testname=getLongOption --aa --bb=bb --cc="cc" --dd dd 2>&1`;
        $expected = "default-bb-cc-dd--";
        $this->assertSame($expected, $output);
    }


    function testWriteVersion()
    {
        $output = `php mycommand.php --version 2>&1`;
        $expected =  "Base Command \nA base command, to be extended.\n";
        $this->assertSame($expected, $output);

        $cmd = new Command();
        $config = $cmd->getConfig();
        $config["version"] = "1.2.3";
        $config["authors"] = ["Florent", "Florian"];
        $config["description"] = "A superb\ncommand";
        $cmd->setConfig($config);

        $expected =  "Base Command (v1.2.3) by Florent, Florian\nA superb\ncommand\n";
        $this->expectOutputString($expected);
        $cmd->writeVersion();
    }

    function testRenderTable()
    {
        $expected =  "col1  col2lastcol\n";
        $expected .= "row11 row1row13\n";
        $expected .= "row21 row2row233333333\n";

        $headers = ["col1  ", "col2", "lastcol"];
        $rows = [
            ["row11", "row12", "row13"],
            ["row21", "row22", "row233333333"],
        ];

        $cmd = new Command();
        $table = $cmd->renderTable($headers, $rows);
        $this->assertSame($expected, $table);


        $expected =  "col1   | col2 | lastcol\n";
        $expected .= "row11  | row1 | row13\n";
        $expected .= "row21  | row2 | row233333333\n";

        $headers = ["col1  ", "col2", "lastcol"];
        $rows = [
            ["row11", "row12", "row13"],
            ["row21", "row22", "row233333333"],
        ];

        $cmd = new Command();
        $table = $cmd->renderTable($headers, $rows, " | ");
        $this->assertSame($expected, $table);
    }

    function testWriteHelp()
    {
        $expected =  "Usage: Not much to do with it in the cmd line, please extends the class to create your own console application.\n";
        $expected .= "              \n";
        $expected .= "    --version Shows the name, version, authors and description.\n";
        $expected .= "    --help    Shows the usage and option list.\n";

        $cmd = new Command();
        $output = $cmd->getHelp();
        $this->assertSame($expected, $output);
    }

    function testColoredText()
    {
        $cmd = new Command();

        $text = $cmd->getColoredText("a value");
        $this->assertSame("a value", $text);

        $text = $cmd->getColoredText("a value", "default");
        $this->assertSame("\033[40ma value\033[m", $text);

        $text = $cmd->getColoredText("a value", 5);
        $this->assertSame("\033[45ma value\033[m", $text);

        $text = $cmd->getColoredText("a value", Command::COLOR_BLUE, "red");
        $this->assertSame("\033[44;31ma value\033[m", $text);

        $text = $cmd->getColoredText("a value", null, Command::COLOR_CYAN);
        $this->assertSame("\033[36ma value\033[m", $text);
    }
}

