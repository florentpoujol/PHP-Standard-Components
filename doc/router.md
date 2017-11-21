# Router

The router allows you to define routes


## Routes

Routes have one uri and one action which is a callable.
They are also attached to one or several HTTP methods.

Routes are immutable, all parameters must be passed to the constructor

```php
$action = function() {
    //
};

$route = new Route("get", "/contact", $action);
```

Named placeholder may be defined in the uri so that the value in that part of the uri is passed to the action.
Placeholder may by default accept any character, but you can define your own rule via regexes which must not contain capturing  groups.

```php
$action = function($id) {
    // build form for user with id $id 
    // or process data from the form and update user in DB
};

$route = new Route(["get", "post"], "/users/{id}/edit", $action);

// same route but the id must be numerical 
$paramConstraints = ["id" => "[0-9]+"];
$route = new Route(["get", "post"], "/users/{id}/edit", $action, $paramConstraints);
```

Placeholders may also be considered optional. Just use square bracket instead of curly ones.  
Default values for optional placeholders may be defined and will be passed to the action if it is missing from the uri.

```php
$action = function($id, $action) {
    //
};

$paramConstraints = [
    "id" => "[0-9]+",
    "action" => "create|show|edit|delete",
];

$paramDefaults = ["action" => "show"];

$route = new Route(["get", "post"], "/users/{id}/[action]", $action, $paramConstraints, $paramDefaults);
```

When the route is `/users/1`, the value `show` will be passed to the action.

When no default value is specified, this argument is not passed to the action, allowing it to use the default value specified in the action's signature, if any.

As for functions, optional parameters should be specified after any mandatory ones.

Placeholders using curly brackets may be optional too, if their constraint allows them to match empty string, ie: `.*`, `|[0-9]+`.

Slashes are always considered optional

## Router

```php
$route = new Route(...);

$router = new Router();
$router->addRoute($route);
// or pass the method(s), uri and action (you cannot pass the params conditions or defaults to addRoute()
$router->addRoute("get", "/", $action);
```

Call the `dispatch()` method to trigger a route. It returns what the action returns, or `false` if no route match.
```php
$router->dispatch($method, $uri);

// or let it determine the method and uri from the $_SERVER surperglobal
$router->dispatch();
```
