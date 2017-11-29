<?php

namespace StdCmp\Console;

class Command
{
    /**
     * @var array Raw argument list. Original $argv argument passed to init() or $_SERVER['argv'].
     */
    protected $argv = [];
    protected $arguments = [];
    protected $options = []; // key = name, value = value or null

    public $config = [
        "name" => "Base Command",
        "description" => "A base command, to be extended.",

        "usage" => "Not much to do with it in the cmd line, please extends the class to create your own console application.",

        "tasks" => [
            "version" => [
                "target" => "writeVersion",
                "description" => "Shows the name, version, authors and description."
            ],
            "help" => [
                "target" => "writeHelp",
                "description" => "Shows the usage and option list."
            ],
            "list" => [
                "target" => "writeTaskList",
                "description" => "Shows the task list."
            ],
        ],
    ];

    public function __construct(array $argv = null)
    {
        if ($argv === null) {
            $argv = $_SERVER["argv"];
        }
        $this->argv = $argv;

        array_shift($argv); // remove the file name which is always the first argument
        // build the argument and option lists
        foreach ($argv as $arg) {
            if ($arg[0] === "-") {
                $parts = explode("=", $arg);
                $parts[0] = str_replace("-", "", $parts[0]);
                $this->options[$parts[0]] = $parts[1] ?? null;
                // We cannot get options values here when they are separated by a space
                // since they may be actual arguments
                // unless we know that the preceding option expect a mandatory value.
                // Same where the value is appended without = sign
            } else {
                $this->arguments[] = $arg;
            }
        }

        // create the arrays if they don't so that we don't have to check if they exists everytime we want to use them
        if (!isset($this->config["options"])) {
            $this->config["options"] = [];
        }
        if (!isset($this->config["optionAliases"])) {
            $this->config["optionAliases"] = [];
        }
        if (!isset($this->config["argumentNames"])) {
            $this->config["argumentNames"] = [];
        }
        if (!isset($this->config["tasks"])) {
            $this->config["tasks"] = [];
        }

        if (isset($this->arguments[0])) {
            $this->runTask($this->arguments[0]);
        }
    }

    /**
     * @return mixed
     */
    public function runTask(string $name)
    {
        if (!isset($this->config["tasks"][$name])) {
            return null;
        }

        $target = $this->config["tasks"][$name];
        if (is_array($target) && isset($target["target"])) {
            $target = $target["target"];
        }

        if (is_string($target) && method_exists($this, $target)) {
            return $this->{$target}();
        }

        if (is_callable($target)) {
            return $target($this);
        }

        if (class_exists($target)) {
            $argv = $this->argv;
            array_splice($argv, 1, 1); // remove task name (second argument)
            return new $target($argv);
        }

        throw new \UnexpectedValueException("Sub command '$name' value is not a callable or doesn't extend Command");
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string $name)
    {
        $id = array_search($name, $this->config["argumentNames"]);
        if ($id !== false) {
            return $this->arguments[$id] ?? null;
        }
        return null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getOption(string $name, $defaultValue = null)
    {
        $value = $this->options[$name] ?? $defaultValue;

        if ($value === null) {
            // option name not in the options array, or with null value
            // and default value is null, so suppose the option has a mandatory value
            // value is not already in the options array if the option value is appended to the option name or separated by a space
            // (not testable in a non-cli context)
            $queryName = "$name:";
            $longOpt = [];
            if (strlen($name) >= 2) {
                $longOpt = [$queryName];
                $queryName = "";
            }
            $value = getopt($queryName, $longOpt);
            $value = $value[$name] ?? $defaultValue;
        }

        return $value;
    }

    public function hasOption(string $name): bool
    {
        $hasOption = array_key_exists($name, $this->options) !== false;
        // do not use isset() here since it would return false
        // for keys existing in option but with the null value

        if ($hasOption === false) {
            // options which value is appended to the name are not in the options array
            // not has their actual name, but as the name+value appended
            // so look for it with getopt() for a more precise search
            // (not testable in a non-cli context)
            $queryName = "$name:";
            $longOpt = [];
            if (strlen($name) >= 2) {
                $longOpt = [$queryName];
                $queryName = "";
            }
            $value = getopt($queryName, $longOpt);
            $hasOption = isset($value[$name]);
        }

        return $hasOption;
    }

    public const COLOR_DEFAULT = "default";
    public const COLOR_RED = "red";
    public const COLOR_GREEN = "green";
    public const COLOR_YELLOW = "yellow";
    public const COLOR_BLUE = "blue";
    public const COLOR_MAGENTA = "magenta";
    public const COLOR_CYAN = "cyan";
    public const COLOR_GRAY = "gray";

    protected $colors = [
        "default" => "0",
        "red" => "1",
        "green" => "2",
        "brown" => "3",
        "blue" => "4",
        "magenta" => "5",
        "cyan" => "6",
        "gray" => "7",
        "other1" => "8",
    ];

    public function getColoredText(string $value, string $bgColor = null, string $textColor = null)
    {
        $color = "";
        if ($bgColor !== null) {
            if (!is_numeric($bgColor)) {
                $bgColor = $this->colors[$bgColor] ?? "";
            }
            if ($bgColor !== "") {
                $bgColor = "4$bgColor";
            }
            $color = $bgColor;
        }

        if ($textColor !== null) {
            if (!is_numeric($textColor)) {
                $textColor = $this->colors[$textColor] ?? "";
            }
            if ($textColor !== "") {
                $textColor = "3$textColor";
            }
            if ($color !== "" && $textColor !== "") {
                $color .= ";";
            }
            $color .= $textColor;
        }

        if ($color !== "") {
            $value = "\033[{$color}m" . $value . "\033[m";
        }

        return $value;
    }

    public function write(string $value, string $bgColor = null, string $textColor = null)
    {
        if ($bgColor !== null || $textColor !== null) {
            $value = $this->getColoredText($value, $bgColor, $textColor);
        }

        echo $value . "\n";
    }

    public function renderTable(array $headers, array $rows, string $colSeparator = ""): string
    {
        $colWidths = [];
        foreach ($headers as $id => $header) {
            if ($id !== 0 && $colSeparator !== "") {
                $header = $colSeparator.$header;
                $headers[$id] = $header;
            }
            $colWidths[] = strlen($header);
        }
        $colWidths[count($colWidths) - 1] = -1; // no restrictions on last column

        $table = implode("", $headers) . "\n";

        foreach ($rows as $row) {
            foreach ($row as $colId => $cell) {
                if ($colId !== 0 && $colSeparator !== "") {
                    $cell = $colSeparator.$cell;
                }

                $targetWidth = $colWidths[$colId];
                if ($targetWidth !== -1) {
                    $cellWidth = strlen($cell);
                    if ($cellWidth === $targetWidth) {
                        continue;
                    }

                    $cell = str_pad($cell, $targetWidth);
                    if ($cellWidth > $targetWidth) {
                        $cell = substr($cell, 0, $targetWidth);
                    }
                }
                $row[$colId] = $cell;
            }

            $table .= implode("", $row) . "\n";
        }

        return $table;
    }

    public function writeTable(array $headers, array $rows, string $colSeparator = "")
    {
        echo $this->renderTable($headers, $rows, $colSeparator);
    }

    public function getVersionText(): string
    {
        $version = "";
        if (isset($this->config["name"])) {
            $version .= $this->config["name"] . " ";
        }

        if (isset($this->config["version"])) {
            $version .= "(v" . $this->config["version"] . ") ";
        }

        if (isset($this->config["authors"])) {
            $authors = $this->config["authors"];
            if (is_string($authors)) {
                $authors = [$authors];
            }
            if (! empty($authors)) {
                $authorsStr = "by ";
                foreach ($authors as $author) {
                    $authorsStr .= $author . ", ";
                }
                $version .= substr($authorsStr, 0, -2);
            }
        }
        $version .= "\n";

        if (isset($this->config["description"])) {
            $version .= $this->config["description"] . "\n";
        }

        return $version;
    }

    public function writeVersion()
    {
        echo $this->getVersionText();
    }

    public function getHelpText(): string
    {
        $help = "";
        if (isset($this->config["usage"])) {
            $usage = $this->config["usage"];
            if (substr($usage, 0, 6) !== "Usage:") {
                $usage = "Usage: $usage";
            }

            $help .= $usage . "\n";
        }

        if (isset($this->config["options"])) {
            $headers = ["    ", "          ", ""];
            $help .= $this->renderTable($headers, $this->config["options"]);
        }

        return $help;
    }

    public function writeHelp()
    {
        echo $this->getVersionText();
    }

    public function getTaskListText(): string
    {
        $tasks = $this->config["tasks"] ?? [];
        if (empty($tasks)) {
            return "No task defined.";
        }

        $list = "Available tasks:\n";

        $headers = ["   Name        ", "   Description"];
        $rows = [];
        foreach ($tasks as $name => $task) {
            $description = "";
            if (is_array($task) && isset($task["description"])) {
                $description = $task["description"];
            }

            $rows[] = [$name, $description];
        }

        $list .= $this->renderTable($headers, $rows, "  ");
        return $list . "\n";
    }

    public function writeTaskList()
    {
        echo $this->getTaskListText();
    }

    public function prompt(string $msg = "")
    {
        if ($msg !== "") {
            echo $msg . "\n";
        }

        return rtrim(fgets(STDIN), "\n");
    }

    public function promptConfirm(string $msg = "Confirm ? Write 'y' for yes.")
    {
        if ($msg !== "") {
            echo $msg . "\n";
        }

        $returned = fgets(STDIN);
        return strtolower($returned[0]) === "y";
    }

    public function promptPassword(string $msg = "Password:")
    {
        if ($msg !== "") {
            echo $msg . "\n";
        }

        system('stty -echo'); // will probably not work on windows
        $returned = rtrim(fgets(STDIN), "\n");
        system('stty echo');
        return $returned;
    }
}
