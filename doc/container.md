# Dependency Injection Container

Implements PSR-11.

Containers are in their simplest use a key/value store.

Keys are service names and value an instance of one concrete implementation of such service.
Ie: mailer => new PhpMailer();

They are used to prevent hardcoding dependencies to concrete implementation within your code.

Ie:
```php
//  instead of doing this:
class HardcodedDependency 
{
    function logThing() 
    {
        $logger = new Monolog\Logger();
    }
}

// you would do this
class NoDependency 
{
    function logThing() 
    {
        // the container instance is obtained somehow 
        $logger = $container->get("logger");
    }
}
```

This allows to replace the underling implementation of the mailer simply by changing a value in the container config.  
This also suppose that the implementations have the same API. Facilitating this is precisely one of the goal of the [FIG](http://www.php-fig.org/).

However, this approach makes your class depens on the container.

## Constructor injection

One other typical use is to inject dependencies in object's constructors (or sometimes also to methods).  
In order to swap implementation easily, the object's type declared to the constructor should be an interface.

```php
class DoSomething
{
    function __construct(LoggerInterface $logger, int $otherArgument)
    {
        $this->logger = $logger;
    }
}
```

When you create the object via the container, it will discover and provide all the necessary arguments: `$doSomethingInstance = $container->get(DoSomething::class);`. This is called autowiring.

When arguments are type-hinted against a class, no more configuration is needed.  
But in the case of an interface you would just need to tell the container which class to use when it encounter the interface. 
You can just alias it like so: `$container->set(LoggerInterface::class, Monolog\Logger::class);`.

You can also set a service to the concrete class and alias the interface to the service:
```php
$container->set("logger", Monolog\Logger::class);
$container->set(LoggerInterface::class, "logger");
``` 

When a constructor argument is not type-hinted or is anything other than a class or interface and ha no default value, you need to explicitly tell the container what value to use for this argument.  
You can supply the container with a associative array describing theses arguments. 

```php
$container->set(DoSomething::class, [
    "otherArgument" => 123,
]);
``` 
 
Now, when you ask the container for an instance of DoSomething, it will see that you have supplied a partial list of argument's value.    
It will try to autowire the first argument since it is not in that list and just use the value from the list for the second argument.

When you need to reuse the same value between several argument list but don't have access to another key/value store like your app's configuration, you can use the containers parameter store.

```php
$container->setParameter("some_value", 123);
$container->getParameter("some_value"); // returns 123
```

The advantage is that you can reference that parameter in the argument's list: just prepend the parameter id with a `"%"`:
```php
$container->set(DoSomething::class, [
    "otherArgument" => "%some_value",
]);
```

You can also reference other services in the argument's list by prepending their name with `"@"`:
```php
$container->set(DoSomething::class, [
    "logger" => "@logger", // the key is the argument name, the value is the service name
    "otherArgument" => "%some_value",
]);
```

You can also directly specify a class name, but you also need to prepend it with a at sign.
```php
$container->set(DoSomething::class, [
    "logger" => "@Monolog\Logger::class",
    "otherArgument" => "%some_value",
]);
```

## Closure factories

In some case, creating an usable object is more complicated than injecting stuffs in the constructor.  
For these cases, the value of a service can be any callable that returns an object (or actually anything).  
The callable receive the container as only argument.

```php
$container->set("logger", function(DIContainer $c) {
    $logger = new Monolog\Logger();
    $logger->pushHandler(...);
    // ...
    // maybe fetch some parameter or service with the container $c
    // ...
    return $logger;
});
```

Supplying a callable instead of an instance has two advantages:
- the callable is only called (and thus the object(s) created) the first time they are needed, so that nothing is created if that service is not needed
- the callable can be called several times, so that it returns different instances every times.

## Fetch services

The container provide two method for fetching services:

- `get()` returns the exact same value (same instance) every times
- `make()` creates a new instance or call the callable again every times

You can also use the `has()` method to check if a service exists.
