<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Brick\Service;
use Archict\Core\Event\EventDispatcher;
use Archict\Router\Exception\FailedToCreateRouteException;
use Archict\Router\Exception\HTTP\HTTPException;
use Archict\Router\Exception\RouterException;
use Archict\Router\HTTP\ResponseHandler;
use Archict\Router\Route\RouteCollection;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Normalizer\Format;
use CuyZ\Valinor\Normalizer\Normalizer;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
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
    public function route(): void
    {
        $this->loadRoutes();

        $factory = new HttpFactory();
        try {
            $request = ServerRequest::fromGlobals();
            $path    = $request->getUri()->getPath();
            $route   = $this->route_collection->getMatchingRoute($path, $request->getMethod());

            $attributes = [];
            assert(preg_match($route->route_regex, $path, $attributes) === 1);
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
        } catch (HTTPException $exception) {
            $response = $exception->toResponse();
        }

        assert($response instanceof ResponseInterface);
        $response_handler = new ResponseHandler();
        $response_handler->writeResponse($response);
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

        $cache_value = $this->normalizer->normalize($this->route_collection);
        $this->cache->set(self::CACHE_KEY, $cache_value);
    }
}
