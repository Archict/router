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

namespace Archict\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RouteCollectorEvent
{
    /**
     * @var array<array{
     *     method: Method|non-empty-string,
     *     route: string,
     *     handler: RequestHandler|callable(ServerRequestInterface): (ResponseInterface|string),
     * }>
     */
    private array $routes = [];
    /**
     * @var array<array{
     *     method: Method|non-empty-string,
     *     route: string,
     *     handler: Middleware|callable(ServerRequestInterface): ServerRequestInterface
     * }>
     */
    private array $middlewares = [];

    /**
     * @param Method|non-empty-string $method
     * @param RequestHandler|callable(ServerRequestInterface): (ResponseInterface|string) $handler
     */
    public function addRoute(Method|string $method, string $route, RequestHandler|callable $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'route'   => $route,
            'handler' => $handler,
        ];
    }

    /**
     * @return array<array{
     *      method: Method|non-empty-string,
     *      route: string,
     *      handler: RequestHandler|callable(ServerRequestInterface): (ResponseInterface|string),
     *  }>
     */
    public function getCollectedRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param Method|non-empty-string $method
     * @param Middleware|callable(ServerRequestInterface): ServerRequestInterface $handler
     */
    public function addMiddleware(Method|string $method, string $route, Middleware|callable $handler): void
    {
        $this->middlewares[] = [
            'method'  => $method,
            'route'   => $route,
            'handler' => $handler,
        ];
    }

    /**
     * @return array<array{
     *      method: Method|non-empty-string,
     *      route: string,
     *      handler: Middleware|callable(ServerRequestInterface): ServerRequestInterface
     *  }>
     */
    public function getCollectedMiddlewares(): array
    {
        return $this->middlewares;
    }
}
