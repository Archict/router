<?php
/**
 * MIT License
 *
 * Copyright (c) 2024-Present Kevin Traini
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Archict\Router\Route;

use Archict\Core\Services\ServiceManager;
use Archict\Router\Exception\HTTP\MethodNotAllowedException;
use Archict\Router\Exception\HTTP\NotFoundException;
use Archict\Router\IdentityMiddlewareStub;
use Archict\Router\Method;
use Archict\Router\RequestHandler;
use Archict\Router\ResponseFactory;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RouteCollectionTest extends TestCase
{
    private TreeMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = (new MapperBuilder())->enableFlexibleCasting()->allowSuperfluousKeys()->allowPermissiveTypes()->mapper();
    }

    public function testItCanAddRouteWithRequestHandler(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::assertTrue(
            $collection->addRoute(
                Method::GET,
                'route',
                new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface // phpcs:ignore
                    {
                        return ResponseFactory::build()->get();
                    }
                }
            )
        );
    }

    public function testItCanAddRouteWithCallable(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::assertTrue(
            $collection->addRoute(Method::GET, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
    }

    public function testItCanAddRouteWithClassname(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        $handler    = new class implements RequestHandler {
            public function handle(ServerRequestInterface $request): ResponseInterface // phpcs:ignore
            {
                return ResponseFactory::build()->get();
            }
        };
        self::assertTrue($collection->addRoute(Method::GET, 'route', $handler::class));
    }

    public function testItNotAcceptIfAddTwiceSameRoute(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::assertTrue(
            $collection->addRoute(Method::GET, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
        self::assertFalse(
            $collection->addRoute(Method::GET, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
    }

    public function testItAcceptSameRouteDifferentMethod(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::assertTrue(
            $collection->addRoute(Method::GET, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
        self::assertTrue(
            $collection->addRoute(Method::POST, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
    }

    public function testItNotAcceptSameRouteWithMethodAll(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::assertTrue(
            $collection->addRoute(Method::GET, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
        self::assertFalse(
            $collection->addRoute(Method::ALL, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
    }

    public function testItNotAcceptSameRouteIfFirstIsMethodAll(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::assertTrue(
            $collection->addRoute(Method::ALL, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
        self::assertFalse(
            $collection->addRoute(Method::GET, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
    }

    public function testItAcceptSeveralRoute(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::assertTrue(
            $collection->addRoute(Method::GET, 'route1', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
        self::assertTrue(
            $collection->addRoute(Method::GET, 'route2', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
        self::assertTrue(
            $collection->addRoute(Method::GET, 'route3', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
    }

    public function testItAcceptSameRouteWithDifferentGroup(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::assertTrue(
            $collection->addRoute(Method::GET, '{group:\d+}', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
        self::assertTrue(
            $collection->addRoute(Method::ALL, '{group:\a+}', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()) // phpcs:ignore
        );
    }

    public function testItThrowIfRouteNotFound(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::expectException(NotFoundException::class);
        $collection->getMatchingRoute('', 'GET');
    }

    public function testItThrowIfMethodNotAllowed(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        $collection->addRoute(Method::GET, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()); // phpcs:ignore
        self::expectException(MethodNotAllowedException::class);
        $collection->getMatchingRoute('route', 'POST');
    }

    public function testItCanFindMatchingRouteSimple(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        $collection->addRoute(Method::ALL, 'route', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()); // phpcs:ignore
        $route = $collection->getMatchingRoute('route', 'PATCH');

        self::assertSame(Method::ALL, $route->method);
        self::assertSame('route', $route->route);
    }

    public function testItCanFindMatchingRouteWithGroup(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        $collection->addRoute(Method::GET, 'article/{id:\d+}', static fn(ServerRequestInterface $request) => ResponseFactory::build()->get()); // phpcs:ignore
        $route = $collection->getMatchingRoute('article/5', 'GET');

        self::assertSame(Method::GET, $route->method);
        self::assertSame('article/{id:\d+}', $route->route);
    }

    public function testItCanAddMiddlewareWithCallable(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::expectNotToPerformAssertions();
        $collection->addMiddleware(Method::GET, 'route', static fn(ServerRequestInterface $request) => $request);
    }

    public function testItCanAddMiddlewareWithMiddleware(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::expectNotToPerformAssertions();
        $collection->addMiddleware(Method::GET, 'route', new IdentityMiddlewareStub());
    }

    public function testItCanAddMiddlewareWithClassname(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::expectNotToPerformAssertions();
        $collection->addMiddleware(Method::GET, 'route', IdentityMiddlewareStub::class);
    }

    public function testItAcceptMultipleMiddlewareOnSameRoute(): void
    {
        $collection = new RouteCollection(new ServiceManager($this->mapper));
        self::expectNotToPerformAssertions();
        $collection->addMiddleware(Method::GET, 'route', new IdentityMiddlewareStub());
        $collection->addMiddleware(Method::GET, 'route', new IdentityMiddlewareStub());
        $collection->addMiddleware(Method::GET, 'route', new IdentityMiddlewareStub());
    }
}
