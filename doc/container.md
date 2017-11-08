# Dependency Injection Container

Containers are in their simplest use a key/value store.

Keys are service names and value an instance of one concrete implementation of such service.
Ie: mailer => new PhpMailer();

They are used to prevent hardcoding dependencies to concrete implementation within your code.

Ie:
```
//  instead of doing this:
class HardcodedDependency 
{
    function sendEmail() 
    {
        $mailer = new PHPMailer();
    }
}

// you would do this
class NoDependency 
{
    function sendEmail() 
    {
        // the container instance is obtained somehow 
        $mailer = $container->get("mailer");
    }
}
```

This allows to replace the underling implementation of the mailer simply by changing a value in the container config.  
This also suppose that the implementations have the same API. Facilitating this is precisely one of the goal of the [FIG](http://www.php-fig.org/).

## Constructor injection

One other typical use is to inject dependencies to object's constructors (or sometimes also to methods).  
In order to swap implementation easily, the object's type declared to the constructor is often an interface.

```
class DoSomething
{
    function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
}
```

The container's config both
 - map the interface to an instance of a concrete implementation and 
 - register the constructor argument's list to the class name
 
When the container is smart enough to discover that last part by itself, it is called auto-wiring. 

```
$container = new DIContainer();

$container->set("classnameOrService", function(Container $c) {
    return new ClassName();
});
// the closure is just used to delay the creation of the object

// the value can be anything (existing instances, scalar...)
$container->set("classnameOrService", $instance);

// set alias
$container->set("Psr\LoggerInterface", "Monolog\Logger");

// describe an argument list
$container->set("ClassName", [

];

// describe some of the arguments, let autowiring do the job for the others
$container->set("ClassName", [
    "argName1" => "a string",
    "argName2" => "%a_parameter_name",
    "argName3" => "@a_service_name",
];

// 
$container->has("service"); // just returns if an entry exists
$container->get("service"); // returns the same instance everytime
$container->make("service"); // returns a new instance everytime
```

```
// config
$container = new DIContainer($config);
return [
    "something" => any value
    "classname" => closure or instance
    "interface" => "classname"
];
```

Needs to differential the services from the parameters.
Parameters are simple key/value.
Principal use is to configure the container without needing the application's separate config object.
 
```
$container = new DIContainer($services, $parameters);

$container->setParameter(key, value);
$container->getParameters();
$container->setParameters(params);

$container->set(name, callable);
$container->set(name, callable, isFactory);

$container->setAlias(name, alias);


$container->set(service, value); // return same instance
$container->get(service); // return same instance
$container->make(service); // return new instance
$container->has(service);
```

## Cache

