# Log

This Logging library provides standard facilities for logging information to various devices.

The Logger object (PSR-3 Compliant) will uses several helper callables (Processors, Writers, Filters and Formatters) to change the record information and if, how and where the record is saved.

Remember that callables can be any of the following:
- named or anonymous functions,
- named classes that implements the `__invoke()` magic method (anonymous classes do not seems to be able to do this),
- an arrays that contains an object and a method name,
- an arrays that contains a class name and a static method name
- or a string formatted like this: `ClassName::staticMethodName`

The `StdCmp\Log` namespace is introduced by this component. 
All example below assume you have one of the relevant use imports, ie:

```
use StdCmp\Log\Interfaces;
use StdCmp\Log\Processors;
use StdCmp\Log\Writers;
use StdCmp\Log\Filters;
use StdCmp\Log\Formatters;
```

## Basic usage

```
$logger = new Logger("path/to/file.log");

$logger->warning("something is somehow wrong");
// add the following string to the specified log file
// [2017-10-27 04:55:00]: warning (4): something is somehow wrong

// let's say $user is an entity and it's nammed Florent
$logger->error("User {name} did something terrible", ["name" => $user->name]);
// [2017-10-27 04:55:00]: error (3): User Florent did something terrible
```


## Logger

To log an information, instantiate a logger and call the `log(int priority, string $message[, array context = []])` method.  
You can also call one of the shortcuts. Ie: `debug(string $message[, array context = []])`. There is one for each priority.

```
$logger = new Logger();

$logger->log(LOG_WARNING, "OMG something is wrong");
// or
$logger->warning("OMG something is wrong", ["some" => "context"]); // with some context
```

The log function creates a log record, that is processed by the other helpers. 
Each log record is internally an array containing these top level keys.

- priority (int)
- priority_name (string)
- message (string)
- context (array)
- timestamp (int)
- extra (array)

### Priority

Log priority constants are already defined in PHP. See https://secure.php.net/manual/en/function.syslog.php

```
LOG_EMERG   = 0;
LOG_ALERT   = 1;
LOG_CRIT    = 2;
LOG_ERR     = 3;
LOG_WARNING = 4;
LOG_NOTICE  = 5;
LOG_INFO    = 6;
LOG_DEBUG   = 7;

// The Logger class also define the following constant
const PRORITY_NAMES = [
    "emergency", "alert", "critical", "error",
    "warning", "notice", "info", "debug"
];
```

The logger has a list of at least one writer, and optionally of processors and filters.


## Processor

A processor can be any callable.
It receive the record as single parameters.
It is expected to modify any part of it, then to return it.

Processors are added to the logger or a writer via the `addHelper(callable)` method.    
See also the `getHelpers(): array` and `setHelpers(array)` methods.

Ie:
```
// a way to implement Monolog's "channel"
$channelProcessor = function(array $record) {
    $record["extra"]["channel"] = "channel_name";
    return $record;
}

```

### MessagePlaceholder processor

The `Processors\MessagePlaceholder` class allow to replace placeholders in the log's message based on values found in the context array.

The keys found in the message can be composite, using the dot notation and must be surounded by curly braces.
Composite keys should match nested arrays in the context array.

For any match found, the value is replace in the string and the key discarded from the array.  
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

Values in the context array that are bjects that do not implement `__tostring()`, it is casted to array.

Values in the context array that are arrays are encoded in JSON.

### Datetime Processor

The Datetime transform the value of the record's timestamp into a datetime.

You may pass the desired format and or the timezone to the constructor, otherwise the default value of "Y-m-d H:i:s" and "UTC" will be used.


## Filters

Filters can be any callable.  
They are passed the record as single parameters.  
They must return false to prevent the logger or writer to process.

When on the logger, returning false prevent subsequent processors or filters to be called and the log record to be written at all.
When on the writter, returning false prevent only this writer to write, subsequent writer in the logger's queue are called.

Ie a priority filter:

```
// make the writer work only if the priority is critical, alert or emergency
$priorityFilter = function(array $record) {
    return $record["priority"] <= LOG_CRIT;
    // Remember that the priorities' numerical values are in reverse order as one would naturally expect: the lower the number, the higher the importance.
}
```


## Writers

Writers can be any callable. Typically they are classes that implements the Interfaces\Writer (and that implements the `__invoke()` magic method) so that they can, as the logger, hold processors and/or filtersas well as one formatter.

Add processors or filters via the `addHelper(callable $processorOrFilter)` method.

Add a formatter via the `setFormatter(callable $formatter)` method. If no formatter is set when the writer needs to write the value, the `Formatter\Line` formatter will be used, with its default format.

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





## Formatters

Formatters can be any callable.  
They are passed the record as parameters.  
They must return the data that will be handled by the Writer.  


### PlaceholderReplacer formatter


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
$formatter = Formatters\PDO(PDO $pdo[, array $map]);
```

It returns an array with two keys: "query" (string) and  "data" (array).
