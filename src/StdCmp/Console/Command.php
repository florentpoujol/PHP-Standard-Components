<?php

namespace StdCmp\Console;

class Command
{
    protected $argv = [];
    protected $arguments = [];
    protected $argumentsByName = [];

    public function __construct()
    {
        $argv = $_SERVER["argv"];
        $this->argv = $argv;

        array_shift($argv); // remove the file name which is always the first argument
        foreach ($argv as $arg) {
            if ($arg[0] !== "-") { // remove options
                // we cannot remove options values when they are separated by a space
                // since they may be actual arguments
                // unless we know that the preceding option, if any, expect no value...
                $this->arguments[] = $arg;
            }
        }

        // $this->config = array_merge($this->config, $this->getConfig());
        $this->config = $this->getConfig();

        $argNames = $this->config["argumentNames"] ?? [];
        foreach ($argNames as $id => $name) {
            if (!isset($argv[$id])) {
                break;
            }
            $this->argumentsByName[$name] = $argv[$id];
        }

        if ($this->hasOption("version")) {
            $this->writeVersion();
        } elseif ($this->hasOption("help")) {
            $this->writeHelp();
        }
    }

    protected $config = [
        "name" => "Base Command",
        "description" => "A base command, to be extended.",

        "usage" => "Not much to do with it in the cmd line, please extends the class to create your own console application.",
        "optionList" => [
            ["", "--version", "Shows the name, version, authors and description."],
            ["", "--help", "Shows the usage and option list."],
        ],
    ];

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string $name)
    {
        // return $this->argumentsByName[$name] ?? null;
        $argNames = $this->config["argumentNames"] ?? [];
        $id = array_search($name, $argNames);
        if ($id !== false) {
            return $this->arguments[$id] ?? null;
        }
        return null;
    }

    protected function getopt(string $name, string $append = ""): array
    {
        $longOpt = [];
        $nameLength = strlen($name);
        $name .= $append;

        if ($nameLength < 1) {
            return [];
        }

        if ($nameLength >= 2) {
            $longOpt[] = $name;
            $name = "";
        }
        return getopt($name, $longOpt);
    }

    /**
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getOption(string $name, $defaultValue = null)
    {
        $append = ":";
        if ($defaultValue !== null) {
            $append .= ":";
        }
        $options = $this->getopt($name, $append);

        if ($defaultValue !== null && isset($options[$name]) && $options[$name] === false) {
            // option present but without value
            return $defaultValue;
        }
        return $options[$name] ?? $defaultValue;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->getopt($name)[$name]);
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
        $colSizes = [];
        foreach ($headers as $id => $header) {
            if ($id !== 0 && $colSeparator !== "") {
                $header = $colSeparator.$header;
                $headers[$id] = $header;
            }
            $colSizes[] = strlen($header);
        }
        $colSizes[count($colSizes) - 1] = -1; // no restrictions on last column

        $table = implode("", $headers) . "\n";

        foreach ($rows as $row) {
            foreach ($row as $colId => $cell) {
                if ($colId !== 0 && $colSeparator !== "") {
                    $cell = $colSeparator.$cell;
                }

                $targetLength = $colSizes[$colId];
                if ($targetLength !== -1) {
                    $cellLength = strlen($cell);
                    if ($cellLength === $targetLength) {
                        continue;
                    }

                    $cell = str_pad($cell, $targetLength);
                    if ($cellLength > $targetLength) {
                        $cell = substr($cell, 0, $targetLength);
                    }
                }
                $row[$colId] = $cell;
            }

            $table .= implode("", $row) . "\n";
        }

        return $table;
    }

    public function writeTable(array $headers, array $rows)
    {
        echo $this->renderTable($headers, $rows);
    }

    public function getVersion(): string
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
        echo $this->getVersion();
    }

    public function getHelp(): string
    {
        $help = "";
        if (isset($this->config["usage"])) {
            $usage = $this->config["usage"];
            if (substr($usage, 0, 6) !== "Usage:") {
                $usage = "Usage: $usage";
            }

            $help .= $usage . "\n";
        }

        if (isset($this->config["optionList"])) {
            $headers = ["    ", "          ", ""];
            $help .= $this->renderTable($headers, $this->config["optionList"]);
        }

        return $help;
    }

    public function writeHelp()
    {
        echo $this->getHelp();
    }

    public function prompt(string $msg = "")
    {
        if ($msg !== "") {
            echo $msg . "\n";
        }

        $returned = rtrim(fgets(STDIN), "\n");
        return $returned;
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
