<?php
require __dir__ . "/../../vendor/autoload.php";

$cmd = new \StdCmp\Console\Command();

// stuff to test the hasOption() and getOption() methods
$name = getopt("", ["testname:"]);
$name = $name["testname"] ?? null;

$result = "";
if ($name === "hasShortOption") {
    $result .= $cmd->hasOption("a") . "-";
    $result .= $cmd->hasOption("b") . "-";
    $result .= $cmd->hasOption("f") . "-";
    $result .= $cmd->hasOption("c") . "-";
    $result .= $cmd->hasOption("d") . "-";
    $result .= $cmd->hasOption("e") . "-"; // non existant
    // $result .= print_r($cmd->getOptions(), true);
}
elseif ($name === "hasLongOption") {
    $result .= $cmd->hasOption("aa") . "-";
    $result .= $cmd->hasOption("bb") . "-";
    $result .= $cmd->hasOption("cc") . "-";
    $result .= $cmd->hasOption("dd") . "-";
    $result .= $cmd->hasOption("non_existant_option") . "-";
}
elseif ($name === "getShortOption") {
    $result .= $cmd->getOption("a", "default") . "-";
    $result .= $cmd->getOption("b", "default") . "-";
    $result .= $cmd->getOption("f") . "-";
    $result .= $cmd->getOption("c") . "-";
    $result .= $cmd->getOption("d") . "-";
    $result .= $cmd->getOption("e") . "-";
}
elseif ($name === "getLongOption") {
    $result .= $cmd->getOption("aa", "default") . "-";
    $result .= $cmd->getOption("bb", "default") . "-";
    $result .= $cmd->getOption("cc") . "-";
    $result .= $cmd->getOption("dd") . "-";
    $result .= $cmd->getOption("ee") . "-";
}
elseif ($name === "colorOutput") {
    // this is only a visual test to do manually in a console
    $colors = [
        "white" => "0",
        "red" => "1",
        "green" => "2",
        "brown" => "3",
        "blue" => "4",
        "magenta" => "5",
        "cyan" => "6",
        "gray" => "7",
        "other1" => "8",
    ];
    foreach ($colors as $name => $value) {
        $cmd->write("$name BG", $name);
        $cmd->write("$name FG", null, $name);
    }
}
elseif ($name === "prompt") {
    // this is only a visual test to do manually in a console
    echo "You wrote: '" . $cmd->prompt("Write me something:") . "'\n";
}
elseif ($name === "promptConfirm") {
    // this is only a visual test to do manually in a console
    if ($cmd->promptConfirm("You sure ?")) {
        echo "You are sure \n";
    } else {
        echo "OK nevermind \n";
    }
}
elseif ($name === "promptPassword") {
    // this is only a visual test to do manually in a console
    echo "You wrote: '" . $cmd->promptPassword() . "'\n";
}

echo $result;
