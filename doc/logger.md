# Log

This Logging library provides standard (implements PSR-3), flexible and easily extensible facilities for logging information.

You register the information you want to log (a level, a message and maybe some context) to a Logger object which pass the log record to a Writer, which writes it to a device (file, DB, ...).

Loggers may have several writers, to store the information on several devices.

Both Logger and Writer may pass the record to a series of helpers whose goal is either to change the record's data (change the timestamp to a datetime, for instance), or stop further processing. These helpers are usually called Processors and Filters, respectively.

The Writers usually also works with one Formatter which transform the record into a format suitable to be written.

Despite usually being classes, helpers, writers and formatters can be any callable, which always receive the record as only argument. They all can be reused between several logger/writer.

Remember that callables can be any of the following:
- named or anonymous functions,
- named classes that implements the `__invoke()` magic method (anonymous classes do not seems to be able to do this),
- an arrays that contains an object and a method name,
- an arrays that contains a class name and a static method name
- or a string formatted like this: `ClassName::staticMethodName`

## Simple usage

```php
$logger = new Logger("path/to/file.log");

$logger->warning("something is somehow wrong");
// add the following string to the specified log file
// [2017-10-27 04:55:00]: warning (4): something is somehow wrong

// let's say $user is an entity with name Florent
$logger->error("User {name} did something terrible", ["name" => $user->name]);
// [2017-10-27 04:55:00]: error (3): User Florent did something terrible
```

When passing a path as argument of the constructor, the logger use a default setup:
- it will format the record's statement into a datetime
- it will replace placeholder found in the message with corresponding values in the context array
- it will format the record as a string
- it will append it to the specified file (the provided path can actually be any stream)

Not passing an argument to the controller will require you to add helpers, writers, formaters yourself.

## Logger

To log an information, instantiate a logger and call the `log(string|int $level, string $message[, array $context = []])` method.  
You can also call one of the shortcuts methods (named after the level names).    
Ie: `debug(string $message[, array context = []])`.

```php
$logger = new Logger();

$logger->log(LogLevel::WARNING, "OMG something is wrong"); // LogLevel is Psr\Log\LogLevel
// or 
$logger->log(LOG_WARNING, "OMG something is wrong"); // use the built-in LOG_* constants
// or
$logger->warning("OMG something is {what}", ["what" => "really wrong"]); // with some context
```

The log function creates a log record, that is processed by the other helpers. 
Each log record is internally an array containing these top level keys.

- level (string)
- message (string)
- context (array)
- timestamp (int)

The logger has an optional list of helpers and at least one writer.

## Helpers

Helpers can be any callable, they receive the record as single parameters.

Helpers are added to a logger or writer via the `addHelper(callable)` method.    
See also the `getHelpers(): array` and `setHelpers(array)` methods.

The kind of helper usually called Processor is expected to modify any part of the record, then to return it.

The kind of helper usually called Filter is expected to return `false` to signal that that the logger or writer should stop working immediately.  
So if logger has a filter that returns false, the record is not passed to any writer and thus is not saved at all.
If a writer has a filter that returns false, the writer just bails. If there are more writers queuing for that logger, thay are called.

Ie:
```php
// a way to implement Monolog's "channel"
$channelProcessor = function(array $record) {
    $record["channel"] = "channel_name";
    return $record;
}
```

### MessagePlaceholder processor

The `Helpers\MessagePlaceholder` class allow to replace placeholders in the log's message based on values found in the context array.

The keys found in the message can be composite, using the dot notation and must be surrounded by curly braces.
Composite keys should match nested arrays in the context array.

For any match found, the value is replaced in the string and the key discarded from the array.  
Any key that has no equivalent in the context is left as-is.

```php
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

Values in the context array that are objects and do not implement `__tostring()`, are casted to array.  
Values in the context array that are arrays are encoded in JSON.

### Datetime processor

The `Helpers\Datetime` processor transform the value of the record's timestamp into a datetime.

You may pass the desired format and/or the timezone to the constructor, otherwise the default value of "Y-m-d H:i:s" and "UTC" will be used.

### Filters

Ie a priority filter:

```php
// make the logger or writer work only if the priority is critical, alert or emergency
$priorityHelper = function(array $record): bool {
    return in_array(["critical", "alert", "emergency"], $record["level"]);
    // or
    return array_search($record["level"], Logger::LEVELS) <= LOG_CRIT;
}
```

## Writers

Writers too can be any callable. But typically they are classes (that implements the `__invoke()` magic method) so that they can, as the logger, store a list of helpers well as one formatter.

Add a formatter via the `setFormatter(callable $formatter)` method. If no formatter is set when the writer needs to write the value, the `Formatter\Text` formatter will be used, with its default format.

Their goal is to write the record to a device (file, DB...).  
They receive the record as single argument and are expected to return a boolean specifying to the logger whether or not subsequent writer should also be called. As for filters, returning `false` block further work.

After the helpers have been processed, writers pass the record to the formatter, which returns it in a specific format.  
Then they write the formatted record to the device.

If no writer is set on a logger, a `LogicException` will be thrown.
If you don't want your logger to actually write messages, use an empty closure as a Noop writer.

Ie:
```php
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

```php
$writer = Writer\Stream("php://stderr");
$writer = Writer\Stream("/path/to/file"); // shotcut for files
$writer = Writer\Stream($resource); // shotcut for files
```

### PDO writer

Writes to a database, via PDO.  

Use by default the `Formatters\PDO` formatter.  

It expects its formatter to return an array with two keys: 
- "statement": The par that goes after "INSERT INTO tableName ", to be passed to PDO::prepare() method
- "params": An associative array to be passed to the PDOStatement::execute() method  

```php
$pdo = new \PDO(...);
$writer = Writers\PDO(PDO $pdo, string $tableName);
```

### syslog writer

Allow to write to the syslog.
Use the Text formatter by default.  
Require a formatter that returns a string.

```php
$writer = Writers\Syslog([string $ident = "", int $option = null, int $facility = LOG_USER])
```

## Formatters

Formatters can be any callable. They are passed the record as parameters. They must return the data that will be handled by the Writer.  

### Text formatter

The Text formatter returns a single string of the desired format, with all placeholders replaced by values found in the record.

The default format is : "{timestamp} : {level} : {message} {context}\n";

Ie with some HTML: 
```php
$html = <<<EOL<h2>Log from website</h2>
<ul>
  <li>{timestamp}</li>
  <li>{level}</li>
</ul>
<p>{message}</p>
EOL;

$formatter = Formatters\Text($html);
```

### PDO Formatter

The PDO formatter assume by default that there is a column for each entry of the record.  
This can be changed via the map argument which accept a single array as parameter.  
Save any array or object field as json.

If a key is missing from the record, this column will not be written.

```php
$map = [
    // DB column name => record key
    "date" => timestamp",
    "something" => "context.something",
];
$formatter = Formatters\PDO($map);
```

It returns an array with two keys: "statement" (string) and  "params" (array).
