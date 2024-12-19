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

use Archict\Router\Method;
use Archict\Router\RequestHandler;
use Archict\Router\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final readonly class RouteInformation
{
    /**
     * @param non-empty-string $route_regex
     */
    public function __construct(
        public Method $method,
        public string $route,
        public string $route_regex,
        public RequestHandler $handler,
    ) {
    }

    public static function buildDefaultHEAD(string $uri): self
    {
        return new self(
            Method::HEAD,
            $uri,
            '//',
            new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): string // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
                {
                    return '';
                }
            },
        );
    }

    /**
     * @param Method[] $methods
     */
    public static function buildDefaultOPTIONS(string $uri, array $methods): self
    {
        $header_allow = implode(', ', array_unique(array_map(static fn(Method $method) => $method->value, [...$methods, Method::OPTIONS, Method::HEAD])));

        return new self(
            Method::OPTIONS,
            $uri,
            '//',
            new class($header_allow) implements RequestHandler {
                public function __construct(private readonly string $header_allow)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
                {
                    return ResponseFactory::build()->withHeader('Allow', $this->header_allow)->get();
                }
            },
        );
    }
}
