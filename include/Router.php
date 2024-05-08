<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Brick\Service;
use Archict\Core\Event\EventDispatcher;
use Archict\Router\Exception\FailedToCreateRouteException;
use Archict\Router\Exception\HTTP\HTTPException;
use Archict\Router\Exception\RouterException;
use Archict\Router\Route\MiddlewareInformation;
use Archict\Router\Route\RouteCollection;
use Archict\Router\Route\RouteInformation;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Normalizer\Format;
use CuyZ\Valinor\Normalizer\Normalizer;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

#[Service]
final class Router
{
    private const CACHE_KEY = 'archict/router->route_collection';
    private readonly TreeMapper $mapper;
    private readonly Normalizer $normalizer;
    private RouteCollection $route_collection;

    public function __construct(
        private readonly EventDispatcher $event_dispatcher,
        private readonly CacheInterface $cache,
    ) {
        $this->mapper     = (new MapperBuilder())->allowPermissiveTypes()->mapper();
        $this->normalizer = (new MapperBuilder())->normalizer(Format::json());
    }

    /**
     * @throws RouterException
     * @throws InvalidArgumentException
     */
    public function route(ServerRequestInterface $request): ResponseInterface
    {
        $this->loadRoutes();

        try {
            $path        = $request->getUri()->getPath();
            $route       = $this->route_collection->getMatchingRoute($path, $request->getMethod());
            $middlewares = $this->route_collection->getMatchingMiddlewares($path, $request->getMethod());

            $request = array_reduce(
                $middlewares,
                fn(ServerRequestInterface $request, MiddlewareInformation $middleware) => $this->handleMiddleware($middleware, $request),
                $request,
            );

            $response = $this->handleRoute($route, $request);
        } catch (HTTPException $exception) {
            $response = $exception->toResponse();
        }

        return $response;
    }

    private function handleMiddleware(MiddlewareInformation $middleware, ServerRequestInterface $request): ServerRequestInterface
    {
        $attributes = [];
        assert(preg_match($middleware->route_regex, $request->getUri()->getPath(), $attributes) === 1);
        foreach ($attributes as $key => $value) {
            // preg_match array result should have int key for 'normal' groups and string key for named groups
            if (is_string($key)) {
                $request = $request->withAttribute($key, $value);
            }
        }

        return $middleware->handler->process($request);
    }

    private function handleRoute(RouteInformation $route, ServerRequestInterface $request): ResponseInterface
    {
        $factory    = new HttpFactory();
        $attributes = [];
        assert(preg_match($route->route_regex, $request->getUri()->getPath(), $attributes) === 1);
        foreach ($attributes as $key => $value) {
            // preg_match array result should have int key for 'normal' groups and string key for named groups
            if (is_string($key)) {
                $request = $request->withAttribute($key, $value);
            }
        }

        $response = $route->handler->handle($request);
        if (is_string($response)) {
            $response = $factory->createResponse()->withBody($factory->createStream($response));
        }

        return $response;
    }

    /**
     * @throws RouterException
     * @throws InvalidArgumentException
     */
    private function loadRoutes(): void
    {
        if ($this->cache->has(self::CACHE_KEY)) {
            $cache_value = $this->cache->get(self::CACHE_KEY);
            try {
                $this->route_collection = $this->mapper->map(RouteCollection::class, $cache_value);
            } catch (MappingError) {
                $this->collectRoutes();
            }
        } else {
            $this->collectRoutes();
        }
    }

    /**
     * @throws RouterException
     * @throws InvalidArgumentException
     */
    private function collectRoutes(): void
    {
        $this->route_collection = new RouteCollection();

        $collector        = $this->event_dispatcher->dispatch(new RouteCollectorEvent());
        $collected_routes = $collector->getCollectedRoutes();
        foreach ($collected_routes as $collected_route) {
            $method  = $collected_route['method'];
            $route   = $collected_route['route'];
            $handler = $collected_route['handler'];
            if (is_string($method)) {
                $method = Method::fromString($method);
            }

            if (!$this->route_collection->addRoute($method, $route, $handler)) {
                throw new FailedToCreateRouteException($method, $route);
            }
        }

        $collected_middlewares = $collector->getCollectedMiddlewares();
        foreach ($collected_middlewares as $collected_middleware) {
            $method  = $collected_middleware['method'];
            $route   = $collected_middleware['route'];
            $handler = $collected_middleware['handler'];
            if (is_string($method)) {
                $method = Method::fromString($method);
            }

            $this->route_collection->addMiddleware($method, $route, $handler);
        }

        $cache_value = $this->normalizer->normalize($this->route_collection);
        $this->cache->set(self::CACHE_KEY, $cache_value);
    }
}
