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
use Archict\Router\Middleware;
use Archict\Router\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class RouteHelper
{
    private function __construct()
    {
    }

    /**
     * Regex will be of form '{pattern}'
     *
     * @return non-empty-string
     * @throws RouterException
     */
    public static function routeToRegex(string $route, bool $named_group = true): string
    {
        $route  = ltrim($route, '/');
        $parser = new RouteParser($named_group);

        return $parser->parse(mb_str_split($route));
    }

    /**
     * Regex will be of form '{pattern}'
     *
     * @return non-empty-string
     * @throws RouterException
     */
    public static function routeToMiddlewareRegex(string $route, bool $named_group = true): string
    {
        if ($route === '*') {
            return '{^.*$}';
        }

        $route  = ltrim($route, '/');
        $parser = new RouteParser($named_group);

        return $parser->parse(mb_str_split($route));
    }

    /**
     * @param callable(ServerRequestInterface): (ResponseInterface|string) $handler_function
     */
    public static function callableToRequestHandler(callable $handler_function): RequestHandler
    {
        return new class($handler_function) implements RequestHandler {
            /**
             * Class cannot have a callable as property (php82)
             * @var non-empty-array<callable(ServerRequestInterface): (ResponseInterface|string)>
             */
            private readonly array $handler;

            public function __construct(callable $handler)
            {
                $this->handler = [$handler];
            }

            public function handle(ServerRequestInterface $request): ResponseInterface|string
            {
                return $this->handler[0]($request);
            }
        };
    }

    /**
     * @param callable(ServerRequestInterface): ServerRequestInterface $handler_function
     */
    public static function callableToMiddleware(callable $handler_function): Middleware
    {
        return new class($handler_function) implements Middleware {
            /**
             * Class cannot have a callable as property (php82)
             * @var non-empty-array<callable(ServerRequestInterface): ServerRequestInterface>
             */
            private readonly array $handler;

            public function __construct(callable $handler)
            {
                $this->handler = [$handler];
            }

            public function process(ServerRequestInterface $request): ServerRequestInterface
            {
                return $this->handler[0]($request);
            }
        };
    }
}
