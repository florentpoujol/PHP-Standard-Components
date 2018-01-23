<?php

namespace StdCmp\DI;

use Psr\Container\ContainerInterface;

class DIContainer implements ContainerInterface
{
    /**
     * @var array
     */
    protected $services = [];

    /**
     * Values cached by get().
     * Typically object instances, but may be any values returned by closures or found in services.
     */
    protected $cached = [];

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

    /**
     * @param mixed $value
     */
    public function setParameter(string $name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @return mixed
     */
    public function getParameter(string $name)
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * @param object|array|callable|string $value Object instance, service alias, constructor arguments, object factory.
     */
    public function set(string $serviceName, $value)
    {
        if (is_object($value) && ! is_callable($value)) {
            // an object instance
            $this->cached[$serviceName] = $value;
            return;
        }

        $this->services[$serviceName] = $value;
    }

    public function has($serviceName)
    {
        return isset($this->cached[$serviceName]) || isset($this->services[$serviceName]);
    }

    public function get($serviceName)
    {
        if (isset($this->cached[$serviceName])) {
            return $this->cached[$serviceName];
        }

        $value = $this->make($serviceName);
        $this->cached[$serviceName] = $value;
        return $value;
    }

    /**
     * Returns a new instance of objects or call again a callable.
     * @param string $serviceName
     * @return mixed|null
     * @throws \Exception
     */
    public function make(string $serviceName)
    {
        if (! isset($this->services[$serviceName])) {
            if (class_exists($serviceName)) {
                return $this->createObject($serviceName);
            }

            throw new \Exception("Service '$serviceName' not found.");
        }

        $value = $this->services[$serviceName];

        // check if is a callable first, because callables can be string or array, too
        if (is_callable($value)) {
            return $value($this);
        }

        if (is_array($value)) {
            // $name is class name, $value is class constructor description
            return $this->createObject($serviceName, $value);
        }

        if (is_string($value)) {
            // class name or alias to other service

            // resolve alias as deep as possible
            $valueChanged = false;
            while (isset($this->services[$value])) {
                $value = $this->services[$value];
                $valueChanged = true;
            }

            if ($valueChanged) {
                return $this->make($value);
            }

            if (class_exists($value)) {
                return $this->createObject($value);
            }

            throw new \UnexpectedValueException("Service '$serviceName' resolve to a string value '$value' that is neither another known service nor a class name.");
        }

        // by this point $value is not an object factory(callable), constructor arguments (array) or an alias (string)
        // it's also something else than an object instance since set() already cache instance that would be passed to it
        $type = gettype($value);
        throw new \UnexpectedValueException("Unexpected value with type '$type' for service '$serviceName'");
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
            $isParamMandatory = ! $param->isOptional();

            $typeName = "";
            $typeIsBuiltin = false;
            $type = $param->getType();
            if ($type !== null) {
                $typeName = (string)$type;
                $typeIsBuiltin = $type->isBuiltin();
            }

            if (isset($manualArguments[$paramName])) {
                $value = $manualArguments[$paramName];

                if (is_string($value)) {
                    if ($value[0] === "@") { // service reference
                        $value = $this->make(str_replace("@", "", $value));
                        // shoudn't make() be called here when createObject() is called from make() ?
                        // could allow user to prepend service name with @@ instead of @ to use either get or make
                    } elseif ($value[0] === "%") { // parameter reference
                        $value = $this->getParameter(str_replace("%", "", $value));
                    }
                }

                $args[] = $value;
                continue;
            }

            if ($typeName === "" || $typeIsBuiltin) {
                // no type hint or not an object
                if ($isParamMandatory) {
                    throw new \Exception("Constructor argument '$paramName' for class '$className' has no type-hint or is of built-in type '$typeName' but value is not manually specified in the container.");
                }
                continue;
            }

            // param is a class or interface (internal or userland)
            if (interface_exists($typeName) && !$this->has($typeName)) {
                throw new \Exception("Constructor argument '$paramName' for class '$className' is type-hinted with the interface '$typeName' but no alias for it is set in the container.");
            }

            $object = null;

            if ($isParamMandatory) {
                try {
                    $object = $this->get($typeName);
                } catch (\Exception $exception) {
                    throw new \Exception("Constructor argument '$paramName' for class '$className' has type '$typeName' but the container don't know how to resolve it.");
                }
            }

            $args[] = $object;
        }

        return new $className(...$args);
    }
}
