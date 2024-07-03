<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Brick\Service;
use Archict\Core\Event\EventDispatcher;
use Archict\Core\Services\ServiceManager;
use Archict\Router\Config\ConfigurationValidator;
use Archict\Router\Config\RouterConfiguration;
use Archict\Router\Exception\ErrorHandlerShouldImplementInterfaceException;
use Archict\Router\Exception\FailedToCreateRouteException;
use Archict\Router\Exception\HTTP\HTTPException;
use Archict\Router\Exception\HTTPCodeNotHandledException;
use Archict\Router\Exception\RouterException;
use Archict\Router\HTTP\FinalResponseHandler;
use Archict\Router\Route\MiddlewareInformation;
use Archict\Router\Route\RouteCollection;
use Archict\Router\Route\RouteInformation;
use GuzzleHttp\Psr7\HttpFactory;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

#[Service(RouterConfiguration::class, 'router.yml')]
final class Router
{
    private RouteCollection $route_collection;
    private ?ResponseInterface $response;
    private ?ServerRequestInterface $request;

    /**
     * @throws HTTPCodeNotHandledException
     * @throws ErrorHandlerShouldImplementInterfaceException
     */
    public function __construct(
        private readonly EventDispatcher $event_dispatcher,
        private readonly RouterConfiguration $configuration,
        private readonly ServiceManager $service_manager,
    ) {
        (new ConfigurationValidator())->validate($this->configuration);
    }

    /**
     * @throws RouterException
     * @throws InvalidArgumentException
     */
    public function route(ServerRequestInterface $request): void
    {
        $this->loadRoutes();

        $this->request = $request;
        try {
            $path        = ltrim($this->request->getUri()->getPath(), '/');
            $route       = $this->route_collection->getMatchingRoute($path, $this->request->getMethod());
            $middlewares = $this->route_collection->getMatchingMiddlewares($path, $this->request->getMethod());

            $this->request = array_reduce(
                $middlewares,
                fn(ServerRequestInterface $mid_request, MiddlewareInformation $middleware) => $this->handleMiddleware($middleware, $path, $mid_request),
                $this->request,
            );

            $this->response = $this->handleRoute($route, $path, $this->request);
        } catch (HTTPException $exception) {
            $this->response = $exception->toResponse();
        } catch (Throwable $throwable) {
            $this->response = HTTPExceptionFactory::ServerError($throwable->getMessage())->toResponse();
        }
    }

    public function response(): void
    {
        if ($this->response === null || $this->request === null) {
            throw new LogicException('You should call Router::route() before');
        }

        $response = $this->response;
        $code     = $this->response->getStatusCode();
        if (isset($this->configuration->error_handling[$code])) {
            $handler = $this->configuration->error_handling[$code];
            if (class_exists($handler)) {
                $object = $this->service_manager->instantiateWithServices($handler);
                assert($object instanceof ResponseHandler);
                $response = $object->handleResponse($response, $this->request);
            } else {
                $factory  = new HttpFactory();
                $response = $response->withBody($factory->createStream($handler));
            }
        }

        $final_handler = new FinalResponseHandler();
        $final_handler->writeResponse($response);
    }

    private function handleMiddleware(MiddlewareInformation $middleware, string $path, ServerRequestInterface $request): ServerRequestInterface
    {
        $attributes = [];
        $match      = preg_match($middleware->route_regex, $path, $attributes);
        assert($match === 1);
        foreach ($attributes as $key => $value) {
            // preg_match array result should have int key for 'normal' groups and string key for named groups
            if (is_string($key)) {
                $request = $request->withAttribute($key, $value);
            }
        }

        return $middleware->handler->process($request);
    }

    private function handleRoute(RouteInformation $route, string $path, ServerRequestInterface $request): ResponseInterface
    {
        $factory    = new HttpFactory();
        $attributes = [];
        $match      = preg_match($route->route_regex, $path, $attributes);
        assert($match === 1);
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
    }
}
