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

use Archict\Router\Exception\RouterException;
use Archict\Router\Method;
use Archict\Router\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class RouteCollection
{
    /**
     * @var array<non-empty-string, RouteInformation[]>
     */
    private array $routes = [];

    /**
     * @param RequestHandler|callable(ServerRequestInterface): (ResponseInterface|string) $handler
     * @throws RouterException
     */
    public function addRoute(Method $method, string $route, RequestHandler|callable $handler): bool
    {
        $route_regex = RouteHelper::routeToRegex($route, false);
        if ($this->hasRoute($method, $route_regex)) {
            return false;
        }

        if (!isset($this->routes[$route_regex])) {
            $this->routes[$route_regex] = [];
        }

        $this->routes[$route_regex][] = new RouteInformation(
            $method,
            $route,
            RouteHelper::routeToRegex($route),
            is_callable($handler) ? RouteHelper::callableToRequestHandler($handler) : $handler,
        );

        return true;
    }

    private function hasRoute(Method $method, string $route_regex): bool
    {
        if (isset($this->routes[$route_regex])) {
            $route_informations = $this->routes[$route_regex];
            foreach ($route_informations as $route_information) {
                if ($route_information->method === $method || $route_information->method === Method::ALL || $method === Method::ALL) {
                    return true;
                }
            }
        }

        return false;
    }
}
