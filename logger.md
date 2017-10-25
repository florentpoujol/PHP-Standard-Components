# Log

This Logging library provides standard facilities for logging information of various priority to various devices.

Each log record is made up of:
- the priority,
- the priority name,
- the textual message,
- a context array that my hold any arbitrary data

The Logger object (PSR-3 Compliant) will uses several helper callables to change the record information and if, how and where the record is saved.

Processors may change the content of the record. Each logger may have several processors.

Writers will write the record to a device (file, DB...).
They first process 0 or more Filters to know if the should write the record.
Then they pass the record to a single Formatter that returns it in a specific format.
Then they write the formatted record to the device.
They can be any callable but they are generally expected to be classes so that they can hold a list of Filters and a Formatter.

Filters answers the question "Should the writer write that record ?". They return true or false based on some conditions. If any filter return false, the writer bails.

Formatters return the record, formatted in a particular way.

Remember that callables can be any of the following:
- named or anonymous functions,
- named or anonymous classes that implements the `__invoke()` magic method,
- an arrays that contains an object and a method name,
- an arrays that contains a class name and a static method name, or a string with this structure: `ClassName::staticMethodName`

The `StdCmp\Log` namespace is introduced. 
All example below assume you have one of the relevant use import below.

```
use StdCmp\Log\Interfaces;
use StdCmp\Log\Processors;
use StdCmp\Log\Writers;
use StdCmp\Log\Filters;
use StdCmp\Log\Formatters;
```

## Priority

Log priority constants are already defined in PHP. See https://secure.php.net/manual/en/function.syslog.php

LOG_EMERG   = 0;
LOG_ALERT   = 1;
LOG_CRIT    = 2;
LOG_ERR     = 3;
LOG_WARNING = 4;
LOG_NOTICE  = 5;
LOG_INFO    = 6;
LOG_DEBUG   = 7;

The Logger class also define the following constant
const PRORITY_NAMES = [
    "emergency", "alert", "critical", "error",
    "warning", "notice", "info", "debug"
];


## Logger

To log an information, instantiate a logger and call the `log(int priority, string $message[, array context = []])` method.  
You can also call one of the shortcuts. Ie: `debug(string $message[, array context = []])`. There is one for each priority.

```
$logger = new Logger();

$logger->log(LOG_WARNING, "OMG something is wrong");
// or
$logger->warning("OMG something is wrong", ["some" => "context"]); // with some context
```

Each log record is internally an array containing these top level keys

- priority (int)
- priority_name (string)
- message (string)
- context (array)
- timestamp (int)
- extra (array)


## Processor

A processor can be any callable.

It receive the record as single parameters and must returns it.
It can modify any part of the record,  but is often use to alter the message based on the content of the context array, or add informations in the extra array.

Processors are added to the logger via the `addProcessor(callable $processor[, int position])` method.

Ie:
```
// a way to add Monolog's channel
$channelProcessor = function(array $record) {
    $record["extra"]["channel"] = "channel_name";
    return $record;
}

// a simple way of processing placeholders/replacements described in the PSR-3 guideline.
// note that a more flexible Placeholder processor class is provided, see below
$psr3PlaceholdersProcessor = function(array $record) {
    $replacements = [];
    foreach ($record['context'] as $key => $val) {
        $replacements['{'.$key.'}'] = (string) $val;
    }
    $record['message'] = strtr($record['message'], $replacements);
    return $record;
}
```

### Position argument

When adding processors to the logger, they are added in a queue and then processed in order (first added, first processed).

The optional position second argument allow to insert a new processor at the specified position. Any existing processor at the specified position or higher position are pushed back.

This works the same for addFilter() and addWriter(


### Placeholder processor

The `Processors\Placeholder` class allow to replace placeholder in the log's message based on values found in the context array.

The keys found in the message can be composite, using the dot notation and must be surounded by curly braces.
Composite keys should match nested arrays in the context array.

Any that has a place is discarded from the array.  
Any key that has no equivalent in the context is left as-is.

```
$message = "{key} - {key2.key} - {unkown} - {un.known}";
$context = [
    "key" => "value",
    "key2" => [
        "key" => "value2"
    ]
];

// result after processing :
"value - value2 - {unknown} - {un.known}"
```

The values in the context array must be castable to string. They are discarded without error otherwise.
It can also be a DateTime object. In that case you can set a particular format to print when creating the Placeholder instance. Default format is "";

```
$proc = new Processors\Placeholder([string $datetime_format]);
```


## Writers

Writers can be any callable. Typically they are classes that implements the Interfaces\Writer (and that implements the `__invoke()` magic method) so that they can hold 0 or several filters and 0 or 1 formatter.

Add filters via the `addFilter(callable $filter[, int position])` method.

Add a formatter via the `addFormatter(callable $formatter)` method. If no formatter is set when the writer needs to write the value, the `Formatter\Line` formatter will be used, with its default format.

Their goal is to write the record to a device (file, DB...). they receive the record as single argument and are not expected to return anything.

They first process any filters to know if it should write the record. If one filter returns false, the writer bail and subsequent filters are not called. If the logger has more writers after it, they are called.

Then they pass the record to the formatter, which returns it in a specific format.

Then they write the formatted record to the device.

If no writer is set on a logger, a `Exception\NoWriter` will be thrown.
If you don't want your logger to actually write messages, use an empty closure as a Noop writer.

Ie:
```
// ...
$writer = new StdCmp\Log\Writer\Stream("path/to/file.log");
// no filter, default formatter

$logger->addWriter($writer);
// ...
```

Noop writer
```
$logger->addWriter(function($record) {});
```

### Stream writer

Allow to write to any writable streams supported by PHP, which include files.
Also accept a resource as first argument. In that case, the resource is not closed by the writer.

```
$writer = Writer\Stream("php://stderr");
$writer = Writer\Stream("/path/to/file"); // shotcut for files
$writer = Writer\Stream($resource); // shotcut for files
```

### PDO writer

Allow to write to any database, via DPO.  

Use by default the `Formatters\PDO` formatter.  
It expect a formatter that return an array with two keys: "query" (string) and "data" (array).  

Types between the one in the record and the DB must be compatible.

```
$pdo = new PDO(...);
$writer = Writers\PDO(PDO $pdo, string $tableName[, array $map]);
```

### syslog writer

Allow to write to the syslog.
Use the line formatter by default.  
Require a formatter that returns a string.

```
$writer = Writers\Syslog([string $ident = "", int $option = null, int $facility = LOG_USER])
```


## Filters

Filters can be any callable.

They are called before the writer works with the formatter.
They are passed the record as single parameters.
They must return true if the writer is allowed to work, false otherwise.
Returning false prevent only this writer to write. If there are more writer queuing for this logger, other writers will be called.

Ie a priority filter:

Remember that the priorities' numerical values are in reverse order as one would naturally expect: the lower the number, the higher the importance. This will only be important when writing such priority filters.

```
// make the writer work only if the priority is critical, alert or emergency
$priorityFilter = function(array $record) {
    return ($record["priority"] <= LOG_CRIT);
}
```



## Formatters

Formatters can be any callable.

They are passed the record as parameters.
They must return the data that will be handled by the Writer.


### Line formatter

The line formatter returns a single string.

The default line format is : "{datetime} : {priority_name} : {message} {context} {extra}\n";

```
$formatter = Formatters\Line([
    "line" => "bli",
    "datetime" => "Y-m-d"
]);
```

The line can contain any pattern that may be found in the record.
"{datetime} : {extra.channel}.{priority_name} : {message} \n";



### PDO Formatter

The PDO formatter, assume that there is a column for each entry of the record.  
This can be changed via the map argument which accept a single array as parameter.  
Save context and extra (or any array or object field) as json.

If a key is missing from the record, this column will not be written.

```
$map = [
    // [record field] => [column name]
    "timestamp" => "date",
    "context.something" => "something",
    "extra.something" => "something_else"
];
$formatter = Formatters\PDO(array $map);
```

It returns an array with two keys: "query" (string) and  "data" (array).

