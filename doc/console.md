# Console

Let's imagine you execute a `myapp.php` file via the command line.

From that file, you can of course use `$argv` to get the arguments, the `getopt()` function to get the options or read STDIN to prompt the user for more information, for instance.

You can also instantiate the `StdCmp\Console\Command` class, which is a nice wrapper around the common tasks.

```
php myapp.php arg1 arg2 -o "the option 1" --verbose
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


$value = $this->prompt("sdfsdf ?"); // Command::PROMPT_NORMAL
$value = $this->promptConfirm("You sure ?"); // any answer that begins by Y (case-insensitive) is considered true
$value = $this->promptPassword(); // password is no displayed as the user type it


$cmd->write("something"); // Command::WRITE_INFO
$cmd->write("something wrong", Command::COLOR_RED); 
$cmd->write("Success !", "green"); 

$cmd->writeTable($headers, $rows);
```

## Extending the base command

When you extend the Command class, you can set a `getConfig()`method to return an array.

This associative array can have the following key/value pairs
- `name`
- `version`
- `authors`: a single string or an array of strings
- `description`: feel free to make it multi-line

When the command has no argument and the `--version` option, the name, version and authors will be displayed on a first line, and the description on a second one. This can also be done (and overridden) via the `writeVersion()` method.

It can also have the following key/value pairs
- `usage`: a (multi-line) string describing the expected format of the command
- `optionsList`: an array describing the available options. Each entry in the array describe one option and must be an array of exactly three strings: the option short name (or empty string), the long name (or empty string), and the option description.

When the command has no argument and the `--help` option, the usage and option list are displayed. This can also be done (and overridden) via the `writeHelp()` method.

It can also have the following key/value pairs
- `argumentNames` an array of in-order, expected, argument names so that you may get them by name with the `getArgument()` method.
- `optionAliases`: an array of option alias. The key is the alias, the value is one string or an array of strings. Typical use is to alias both short and long option names to the same name. You can use this alias with the option methods.  

```php
<?php
class MyApp extends Command
{
    public function getConfig(): array
    {
        return [
            // --version
            "name" => "MyApp",
            "version" => "1.0.0",
            "authors" => "me",
            "description" => "A command to do something",
            
            // --help
            "usage" => "Usage: ",
            "optionList" => [
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
        
        $value = $cmd->getArgument("firstArg"); // "arg1"
        
        $value = $cmd->getOption("optAlias"); // "the option 1"
        // the user could have used either -o or --option in the cmd line
        
        $this->writeVersion();
        $this->writeHelp();
    }
}
```

## Sub commands

If your app has several commands, you can of course have several files and run them individually like so: `php mycmd1.php`, `php mycmd2.php`...

But as what Laravel's `artisan` command and the Symfony Console component do, you can have one main command and trigger various tasks based on the name of the first argument. 

Instead of having if-else conditions on the name of the first argument, you can simply specify in the main command's config a list of expected values for the first argument and map them to a callable or an object implementing `Command`.

You do that via the `subCommands` key of the `getConfig()` array. Its value is an associative array of argument values and target, the target being a callable or the name of a class implementing `Command`.  
When the target is a callable, it receive the instance of the main command as first argument

```php
<?php
class MyApp extends Command
{
    public function getConfig(): array
    {
        return [
            // ..
            "subCommands" => [
                "task1" => Task1::class,
                "task2" => function(MyApp $cmd) {
                    // ...
                },
            ]
        ];
    }
}

class Task1 extends Command // note that task1 does NOT extends from MyApp
{
    public function getConfig(): array
    {
        return [
            "name" => "MyApp",
            // ...
            "usage" => "...",
        ];
    }
    
    public function __construct() {
        parent::__construct();
        // do stuff
    }
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

To display the usage of the Task1 sub command, just append the --help option:

```
php myapp.php task1 --help
```

## Executing like any command

To be able to write `myapp` instead of `php myapp.php`, you need to 
- add a shebang line as the very first line of the file (the PHP open tags is then on the second line) 
- make the file executable
- symlink it (without the extension) to a bin directory

```
#!/bin/php
<?php
new MyApp();
```
