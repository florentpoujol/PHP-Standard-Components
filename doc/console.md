# Console

Let's imagine you execute a `myapp.php` file via the command line.

From that file, you can of course use `$argv` to get the arguments, the `getopt()` function to get the options or read STDIN to prompt the user for more information, for instance.

But you can also instantiate the `StdCmp\Console\Command` class, which is a nice wrapper around the common tasks.

```
php myapp.php arg1 arg2 -o="the option 1" --verbose
```

```php
<?php
use StdCmp\Console\Command;

$cmd = new Command();

$arguments = $cmd->getArguments(); // ["arg1", "arg2"]

$value = $cmd->getOption("o"); // "the option 1"

$value = $cmd->getOption("a"); // null
$value = $cmd->getOption("a", "default value"); // "default value"

$value = $cmd->hasOption("a"); // false
$value = $cmd->hasOption("verbose"); // true


$value = $this->prompt("What is the meaning of life ?");
if ($this->promptConfirm("You sure ?")) {
    // any answer that begins by Y (case-insensitive) is considered true
}
$pass = $this->promptPassword(); // password is no displayed when the user type it (don't work on Windows)


$cmd->write("something");
$cmd->write("something wrong", Command::COLOR_RED); // red background 
$cmd->write("Success !", "green", Command::COLOR_GRAY); // green background, gray text 

$headers = ["  Col1  ", "Col2"]; // the width of each columns is set by the their number of chars
//$rows = ...
$cmd->writeTable($headers, $rows);
$cmd->writeTable($headers, $rows, "|"); // will separate each column with a pipe character
```

## Extending the base command

When you extend the Command class, you are encouraged to modify the `config` property.

This associative array can have the following key/value pairs
- `name`
- `version`
- `authors`: a single string or an array of strings
- `description`
- `usage`: a string describing the expected format of the command
- `options`: an array describing the available options. Each entry in the array describe one option and must be an array of exactly three strings: the option short name (or empty string), the long name (or empty string), and the option description.
- `argumentNames` an array of in-order, expected, argument names so that you may get them by name with the `getArgument()` method.
- `optionAliases`: an array of option alias. The key is the alias, the value is one string or an array of strings. Typical use is to alias both short and long option names to the same name. You can use this alias with the option methods.

When `version` is the command's first argument, the name, version and authors will be displayed on a first line, and the description on a second one. This can also be done (and overridden) via the `getVersionText()` method.

When `help` is the command's first argument, the usage and option list are displayed. The output can be changed by overriding the `getHelpText()` method.
  

```php
<?php
class MyApp extends Command
{
    public function getConfig(): array
    {
        return [
            "name" => "MyApp",
            "version" => "1.0.0",
            "authors" => "me",
            "description" => "A command to do something",
            
            "usage" => "Usage: ",
            "option" => [
                "short", "long", "description",
            ],
            
            
            "argumentNames" => ["firstArg", "arg2"],
            
            "optionAliases" => [
                "optAlias" => ["o", "option"],
            ],
        ];
    }
    
    public function __construct()
    {
        parent::__construct(); // don't forget !
        
        $value = $this->getArgument("firstArg"); // "arg1"
        
        $value = $this->getOption("optAlias"); // "the option 1"
        // the user could have used either -o or --option in the cmd line
        
        $this->writeVersion();
        $this->writeHelp();
    }
}
```

## Tasks

A task is an action triggered by the value of the command's first argument.

The three default tasks are `version`, `help` (already seen above) and `list`, which list the available tasks.

You can of course define your own tasks via the `tasks` array in the config. The keys are the task names, the value is either its target, or an array with a `target` and `description` key.
 
The target can be:
- the name of one of the command's methods
- a callable (it receive the instance of the command as first argument)
- a class, for which an object is instantiated 
   
This allow to easily build something akin to Laravel's `artisan` command and the Symfony Console component do, you can have one main command for you application and trigger various tasks based on the name of the first argument. 

```php
<?php
class MyApp extends Command
{
    public function __construct()
    {
        $config = $this->config;
        
        $config["tasks"] = array_merge($config["tasks"], [
            "task1" => Task1::class,
            "task2" => function(MyApp $cmd) {
                // ...
            },
        ]);
        
        $this->config = $config;
        parent::__construct();
    }
}

class Task1 extends Command // the tasks do not need to extends Command
{  
    public function __construct() 
    {
        $this->config["name"] = "...";
        // ...
        parent::__construct();
    }
    
    // ...
}
```

All that is needed to execute/instantiate Task1 is to run the file with the `task1` argument :

```
php myapp.php task1
```

With `myapp.php`: 

```php
<?php
new MyApp();
```

Since Task1 extends command, it also has tasks. So you can trigger its `help` task to print its usage:

```
php myapp.php task1 help
```

