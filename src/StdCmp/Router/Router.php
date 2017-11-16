<?php

namespace StdCmp\Router;

class Router
{
    /**
     * @var array [method => [Route]]
     */
    protected $routesByMethod = [];

    /**
     * @param Route|string|string[] $routeOrMethod
     */
    public function addRoute($routeOrMethod, string $uri = null, callable $target = null)
    {
        if (is_string($routeOrMethod)) {
            // $route is the method(s)
            $routeOrMethod = new Route($routeOrMethod, $uri, $target);
        }

        $methods = $routeOrMethod->getMethods();

        foreach ($methods as $method) {
            if (!isset($this->routesByMethod)) {
                $this->routesByMethod[$method] = [];
            }

            $this->routesByMethod[$method][] = $routeOrMethod;
        }
    }

    public function dispatch(string $method = null, string $targetUri = null): bool
    {
        if ($method === null) {
            $method = $_SERVER["REQUEST_METHOD"];
            $targetUri = $_SERVER["REQUEST_URI"];
        }
        $method = strtolower($method);

        if (!isset($this->routesByMethod[$method])) {
            // no routes
            return false;
        }

        $routes = $this->routesByMethod[$method];

        // get the matched route and values from the URL
        $paramsFromUri = false; // is array when a match
        $route = null;
        foreach ($routes as $route) {
            $paramsFromUri = $route->getParamsFromUri($method, $targetUri);
            if ($paramsFromUri !== false) {
                break;
            }
        }

        if ($paramsFromUri === false) {
            // no match
            return false;
        }

        // $paramsFromUri = $route->getMatchedValuesFromUri();
        $callable = $route->getTarget();

        // get the parameters list based on the callable type
        $rFunc = null;
        if (is_string($callable)) {
            if (function_exists($callable)) {
                $rFunc = new \ReflectionFunction($callable);
            } elseif (strpos($callable, "::") !== false) {
                // Class::staticMethod
                $parts = explode("::", $callable);
                $rFunc = new \ReflectionMethod($parts[0], $parts[1]);
            }
        }
        elseif (is_array($callable)) {
            // ["class", "staticMethod"]
            // [$object, "method"]
            $rFunc = new \ReflectionMethod($callable[0], $callable[1]);
        }
        elseif (is_object($callable)) {
            // invokable object or closure
            $rFunc = new \ReflectionMethod($callable, "__invoke");
        }

        $rParams = [];
        if ($rFunc instanceof \ReflectionFunctionAbstract) {
            $rParams = $rFunc->getParameters();
        }

        // build the argument list
        // this is needed because the callable's argument order
        // may not be the same in the uri
        $params = [];
        $paramDefaultValues = $route->getParamDefaultValues();
        foreach ($rParams as $rParam) {
            $name = $rParam->getName();

            $value = null;
            if (isset($paramsFromUri[$name])) {
                $value = $paramsFromUri[$name];
            } elseif (isset($paramDefaultValues[$name])) {
                $value = $paramDefaultValues[$name];
            }

            $params[] = $value;
        }

        // finally call the target
        $callable(...$params);

        return true;
    }
}
