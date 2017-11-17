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
        if (!is_object($routeOrMethod)) {
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

    public function dispatch(string $method = null, string $uri = null)
    {
        if ($method === null) {
            $method = $_SERVER["REQUEST_METHOD"];
            $uri = $_SERVER["REQUEST_URI"];
        }
        $method = strtolower($method);

        if (!isset($this->routesByMethod[$method])) {
            // no routes
            return false;
        }

        $routes = $this->routesByMethod[$method];

        // get the matched route
        $route = null;
        $matchFound = false;
        foreach ($routes as $route) {
            $matchFound = $route->match($method, $uri);
            if ($matchFound) {
                break;
            }
        }
        if (!$matchFound) {
            return false;
        }
        // to prevent looping on all routes and matching them individually against the uri (which is the slowest)
        // we could do a few things:
        // - when routes have a non-regex prefix (like "/user/{id}"), they can be segregated by it
        // - when regex is needed, we could use grouped and chunked regexes
        //   see https://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html

        // get the parameters list based on the callable type
        $callable = $route->getAction();

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
            // ["class", "staticMethod"] [$object, "method"]
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
        $paramsFromUri = $route->getParamsFromUri($uri);
        $paramDefaults = $route->getParamDefaults();
        foreach ($rParams as $rParam) {
            $name = $rParam->getName();

            if (isset($paramsFromUri[$name])) {
                $params[] = $paramsFromUri[$name];
            } elseif (isset($paramDefaults[$name])) {
                $params[] = $paramDefaults[$name];
            } else {
                break;
                // do not set it to null so that the arg isn't passed at all to the target
                // and the callable applies the default value (hopefully) set in its signature
            }
        }

        // finally call the target
        return $callable(...$params);
    }
}
