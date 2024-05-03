# Archict/router

[![Tests](https://github.com/Archict/router/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/Archict/router/actions/workflows/tests.yml)

## Usage

This Brick allows you to setup route with a request handler and add some middleware. Let's see how to do that!

### A simple route ...

There is 2 way for creating a route: Having a whole controller class, or a simple closure.

First you need to listen to the Event `RouteCollectorEvent`:

```php
<?php

use Archict\Brick\Service;
use Archict\Brick\ListeningEvent;
use Archict\Router\RouteCollectorEvent;

#[Service]
class MyService {
    #[ListeningEvent]
    public function routeCollector(RouteCollectorEvent $event) 
    {
    }
}
```

Then just create the route with the Event object:

**With a closure:**

```php
$event->addRoute(Method::GET, '/hello', static fn() => 'Hello World!');
```

The closure can take a `Request` as argument, and return a string or a `Response`.

**With a controller class**

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$event->addRoute(Method::GET, '/hello', new MyController());

class MyController implements RequestHandler {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ResponseFactory::build()
            ->withStatus(200)
            ->withBody('Hello World!')
            ->get();
    }
}
```

Your controller must implement interface `RequestHandler`. The method `handle` can return either a string or
a `Response`.

Please note that you can define only one handler per route.

The first argument of method `RouteCollectorEvent::addRoute` must be a string of an allowed HTTP method. Enum `Method`
contains list of allowed methods. If your route can match multiple method you can pass an array of method,
or `Method::ALL`.

The second argument is your route, it can be a simple static route, or a dynamic one.
In this last case, each dynamic part must be written between `{}`. Inside there is 2 part separated by a `:`, name of
the part, and pattern. The name of the dynamic part allow you to easily retrieve it in `Request` object.

The pattern can be empty, then it will match all characters until next `/` (or the end), or it can be a regex with some
shortcuts:

- `\d+` match digits `[0-9]+`
- `\l+` match letters `[a-zA-Z]+`
- `\a+` match digits and letters `[a-zA-Z0-9]+`
- `\s+` match digits, letters and underscore `[a-zA-Z0-9_]+`

You can also have an optional suffix to your route with `[/suffix]`.

Here is an example: `/article/{id:\d+}[/{title}]`.

If something went wrong along your process, you can throw an exception built with `HTTPExceptionFactory`. The
exception will be caught by the router and used to build a response.

### ... with a middleware

Sometimes you have some treatment to do before handling your request. For that there is middlewares. You can define as
many middlewares as you want. To define one, the procedure is pretty the same as for a simple route:

```php
<?php

use Archict\Brick\Service;
use Archict\Brick\ListeningEvent;
use Archict\Router\RouteCollectorEvent;
use Psr\Http\Message\ServerRequestInterface;

#[Service]
class MyService {
    #[ListeningEvent]
    public function routeCollector(RouteCollectorEvent $event) 
    {
        $event->addMiddleware(Method::GET, '/hello', static function(ServerRequestInterface $request): ServerRequestInterface {
            // Do something
            return $request
        });
        // Or
        $event->addMiddleware(Method::GET, '/hello', new MyMiddleware());
    }
}
```

If you define your middleware with a closure, then it must return a `Request`. If it's an object, then your class must
implement interface `Middleware`:

```php
use Psr\Http\Message\ServerRequestInterface;

class MyMiddleware implements Middleware
{
    public function process(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request;
    }
}
```

You can do whatever you want in your middleware. If something went wrong, the procedure is the same as for
RequestHandler.
