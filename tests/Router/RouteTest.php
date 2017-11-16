<?php

namespace Router;

use StdCmp\Router\Route;
use PHPUnit\Framework\TestCase;
use StdCmp\Router\Router;

function globalFunction($id, $slug)
{
    // var_dump("---------globalFunction----------", $slug, $id);
    $GLOBALS["globalFunction"] = $slug . $id;
}

class RouteTest extends TestCase
{
    /**
     * @var Router
     */
    protected static $srouter;
    /**
     * @var Router
     */
    protected $router;

    public static function setUpBeforeClass()
    {
        self::$srouter = new Router();
    }

    public function setUp()
    {
        $this->router = self::$srouter;
    }

    static function staticMethod($id, $slug)
    {
        // var_dump("---------staticMethod----------", $slug, $id);
        $GLOBALS["staticMethod"] = $slug . $id;
    }

    function __invoke($id, $slug)
    {
        // var_dump("---------__invoke----------", $slug, $id);
        $GLOBALS["invoke"] = $slug . $id;
    }

    function method($slug, $id)
    {
        // var_dump("---------method----------", $slug, $id);
        $GLOBALS["method"] = $slug . $id;
    }

    function testAddRouteAndDispatch()
    {
        $target = function($slug, $id) {
            // var_dump("---------closure----------", $slug, $id);
            $GLOBALS["closure"] = $slug . $id;
        };

        $router = $this->router;

        $route = new Route("get", "/closure/{id}/{slug}", $target);
        $router->addRoute($route);

        $this->assertArrayNotHasKey("closure", $GLOBALS);
        $router->dispatch("get", "/closure/1/ab");
        $this->assertSame("ab1", $GLOBALS["closure"]);


        $route = new Route("get", "/staticMethod/{id}/{slug}", self::class . "::staticMethod");
        $router->addRoute($route);

        $this->assertArrayNotHasKey("staticMethod", $GLOBALS);
        $router->dispatch("get", "/staticMethod/2/bc");
        $this->assertSame("bc2", $GLOBALS["staticMethod"]);


        $route = new Route("get", "/staticMethod2/{id}/{slug}", [self::class, "staticMethod"]);
        $router->addRoute($route);

        unset($GLOBALS["staticMethod"]);
        $this->assertArrayNotHasKey("staticMethod", $GLOBALS);
        $router->dispatch("get", "/staticMethod2/3/cd");
        $this->assertSame("cd3", $GLOBALS["staticMethod"]);


        $route = new Route("get", "/invoke/{id}/{slug}", $this);
        $router->addRoute($route);

        $this->assertArrayNotHasKey("invoke", $GLOBALS);
        $router->dispatch("get", "/invoke/4/de");
        $this->assertSame("de4", $GLOBALS["invoke"]);


        $route = new Route("get", "/method/{id}/{slug}", [$this, "method"]);
        $router->addRoute($route);

        $this->assertArrayNotHasKey("method", $GLOBALS);
        $router->dispatch("get", "/method/5/ef");
        $this->assertSame("ef5", $GLOBALS["method"]);


        $route = new Route("get", "/globalFunction/{id}/{slug}", "\Router\globalFunction");
        $router->addRoute($route);

        $this->assertArrayNotHasKey("globalFunction", $GLOBALS);
        $router->dispatch("get", "/globalFunction/6/fg");
        $this->assertSame("fg6", $GLOBALS["globalFunction"]);
    }

    function testOptionalSegment()
    {
        $target = function ($id, $slug = null) {
            $GLOBALS["optional_id"] = $id;
            $GLOBALS["optional_slug"] = $slug;
        };

        $router = new Router();

        $route = new Route("get", "/{id}[/{slug}[/other]]", $target);
        $router->addRoute($route);

        $router->dispatch("get", "/7");
        $this->assertSame("7", $GLOBALS["optional_id"]);
        $this->assertSame(null, $GLOBALS["optional_slug"]);

        $router->dispatch("get", "/8/abc");
        $this->assertSame("8", $GLOBALS["optional_id"]);
        $this->assertSame("abc", $GLOBALS["optional_slug"]);

        $router->dispatch("get", "/9/def/other");
        $this->assertSame("9", $GLOBALS["optional_id"]);
        $this->assertSame("def", $GLOBALS["optional_slug"]);

        // with optional argument value
        $router = new Router();

        $defaultParamValues = ["slug" => "ghi"];
        $route = new Route("get", "/{id}[/{slug}]", $target, [], $defaultParamValues);
        $router->addRoute($route);

        $router->dispatch("get", "/10");
        $this->assertSame("10", $GLOBALS["optional_id"]);
        $this->assertSame("ghi", $GLOBALS["optional_slug"]);
    }

    function testArgsConditions()
    {
        $target = function (int $id) {
            $GLOBALS["testargscond"] = $id;
        };

        $this->router = new Router();

        $r = new Route("get", "/{id}", $target, ["id" => "[0-9]{1,3}"]);
        $this->router->addRoute($r);


        $call = $this->router->dispatch("get", "/");
        $this->assertSame(false, $call);

        $this->assertArrayNotHasKey("testargscond", $GLOBALS);
        $call = $this->router->dispatch("get", "/123");
        $this->assertSame(true, $call);
        $this->assertSame(123, $GLOBALS["testargscond"]);

        $call = $this->router->dispatch("get", "/12");
        $this->assertSame(true, $call);
        $this->assertSame(12, $GLOBALS["testargscond"]);

        $call = $this->router->dispatch("get", "/1234");
        $this->assertSame(false, $call);
        $this->assertSame(12, $GLOBALS["testargscond"]);
    }

    function testRouteGetUri()
    {
        $route = new Route("get", "/{id}/{slug}", function(){});

        $uri = $route->getUri();
        $this->assertSame("/{id}/{slug}", $uri);

        $uri = $route->getUri(["id" => 123]);
        $this->assertSame("/123/{slug}", $uri);

        $uri = $route->getUri(["id" => 123, "slug" => "whatever"]);
        $this->assertSame("/123/whatever", $uri);
    }
}
