<?php

namespace Tests\Console;

use StdCmp\Console\Command;

class MyApp extends Command
{
    function __construct()
    {
        $this->config["argumentNames"] = ["arg1", "arg2"];
        $tasks = [
            "subCmd:class" => MySubCommand::class,
            "subCmd:callable" => MySubCommand::class . "::doTheStaticStuff",
            "subCmd:closure" => function (Command $cmd) {
                $cmd->write("Closure called");
            },
        ];
        $this->config["tasks"] = array_merge($this->config["tasks"], $tasks);

        // var_dump($this->config["tasks"]);
        parent::__construct();

        // var_dump($this->config["tasks"]);
    }
}

class MySubCommand extends Command
{
    public function __construct()
    {
        parent::__construct();

        $this->doTheStuff();
    }

    function doTheStuff()
    {
        echo "stuff done !";
    }

    static function doTheStaticStuff()
    {
        echo "Static stuff done !";
    }
}
