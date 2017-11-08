<?php

namespace StdCmp\DI;

class DIContainer
{
    protected $services = [];

    // contains the callable that where originally in services
    // their returned value is replaced in services, but callable saved in factories, if the user call make()
    protected $factories = [];

    protected $parameters = [];


    public function __construct(array $services = null, array $parameters = null)
    {
        if ($services !== null) {
            $this->services = $services;
        }

        if ($parameters !== null) {
            $this->parameters = $parameters;
        }
    }

    // params

    public function setParameter(string $name, $value)
    {
        $this->parameters[$name] = $value;
    }

    public function getParameter(string $name)
    {
        return $this->parameters[$name] ?? null;
    }

    // services

    public function set(string $name, $value)
    {
        $this->services[$name] = $value;
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    public function get(string $name)
    {
        if (! isset($this->services[$name])) {
            if (class_exists($name)) {
                // hopefullly autowire can resolve all of the constructor's arguments
                return $this->createObject($name);
            }
            return null;
        }

        $value = $this->services[$name];

        if (is_string($value)) {
            // classname or alias to other service

            // resolve alias
            while (isset($this->services[$value])) {
                $value = $this->services[$value];
            }

            if (is_string($value)) {
                if (class_exists($value)) {
                    // suppose classname (or service that don't exists)
                    return $this->createObject($value);
                }

                throw new \Exception("$name leads to a string value '$value' that is neither a known service nor a class name");
            }
        }

        if (is_array($value)) {
            // $name is class name, $value is class constructor description
            return $this->createObject($name, $value);
        }

        if (is_callable($value)) {
            $func = $value;
            $object = $func($this);
            $this->services[$name] = $object;
            $this->factories[$name] = $func;
            return $object;
        }

        return $value;
    }

    // allow to pass more arguments to the closure after the container,
    // or use reflection to Inject what is needed
    public function make(string $name)
    {
        if (isset($this->factories[$name])) {
            return $this->factories[$name]($this);
        }

        $func = $this->services[$name] ?? null;
        if (! is_callable($func)) {
            return $func;
        }

        $object = $func($this);
        $this->services[$name] = $object;
        // can it be a problem that the returned object is cached when make() is called and not the first time get() is called ?
        $this->factories[$name] = $func;
        return $object;
    }

    /**
     * @param string $className
     * @param array $manualArguments
     * @return mixed
     * @throws \Exception
     */
    protected function createObject(string $className, array $manualArguments = [])
    {
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $args = [];
        $params = $constructor->getParameters();

        foreach ($params as $param) {
            $paramName = $param->getName();

            $reflexionType = $param->getType();
            $typeName = "";
            $typeIsBuiltin = false;
            if ($reflexionType !== null) {
                $typeName = $reflexionType->getName();
                $typeIsBuiltin = $reflexionType->isBuiltin();
            }

            if (isset($manualArguments[$paramName])) {
                $value = $manualArguments[$paramName];

                if (is_string($value)) {
                    if ($value[0] === "@") { // service reference
                        $value = $this->get(str_replace("@", "", $value));
                    } elseif ($value[0] === "%") { // parameter reference
                        $value = $this->getParameter(str_replace("%", "", $value));
                    }
                }

                $args[] = $value;
            }
            elseif ($typeName === "" || $typeIsBuiltin) {
                // no type hint or not an object
                throw new \Exception("Constructor argument '$paramName' for class '$className' has no type-hint or is of built-in type '$typeName' but value is not manually specified in the container.");
            }
            else { // $typeName !== "" && ! $typeIsBuiltin
                // param is a class or interface (internal or userland)

                $object = $this->get($typeName);
                if ($object === null) {
                    // typeName is an interface not binded to an implementation

                    if (interface_exists($typeName)) {
                        throw new \Exception("Constructor argument '$paramName' for class '$className' is type-hinted with the interface '$typeName' but no alias is set in the container.");
                    }

                    throw new \Exception("Constructor argument '$paramName' for class '$className' has type '$typeName' but the container don't know how to resolve it.");
                }

                $args[] = $object;
            }
        }

        return new $className(...$args);
    }
}
