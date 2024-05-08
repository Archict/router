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

use Archict\Router\Exception\EmptyGroupNameException;
use Archict\Router\Exception\RouterException;
use Archict\Router\Exception\UnclosedGroupRoutePatternException;
use Archict\Router\Middleware;
use Archict\Router\RequestHandler;
use Archict\Router\ResponseFactory;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function Psl\Regex\matches;

final class RouteHelperTest extends TestCase
{
    public function testRouteToRegexThrowIfRouteHasUnclosedGroup(): void
    {
        self::expectException(UnclosedGroupRoutePatternException::class);
        RouteHelper::routeToRegex('route/{unclosed:group');
    }

    public function testRouteToRegexThrowIfGroupEmpty(): void
    {
        self::expectException(EmptyGroupNameException::class);
        RouteHelper::routeToRegex('{}');
    }

    /**
     * @dataProvider provideRouteToParseToRegex
     */
    public function testRouteToRegex(string $route, string $expected): void
    {
        self::assertSame($expected, RouteHelper::routeToRegex($route));
    }

    /**
     * @return iterable<string, string[]>
     */
    public static function provideRouteToParseToRegex(): iterable
    {
        $route_to_test = [
            'Empty route'                         => [
                '',
                '{^/?$}',
            ],
            'Simple route'                        => [
                'hello',
                '{^hello/?$}',
            ],
            'Long route'                          => [
                'this/route/is/pretty/long/but/not/sure/why',
                '{^this/route/is/pretty/long/but/not/sure/why/?$}',
            ],
            'Route with one group'                => [
                'a/{name:\d+}/group',
                '{^a/(?<name>[0-9]+)/group/?$}',
            ],
            'Route with multiple groups'          => [
                'article/{id:\s+}/{anchor}',
                '{^article/(?<id>[a-zA-Z0-9_]+)/(?<anchor>.*?)/?$}',
            ],
            'Route with group with empty pattern' => [
                'article/{id:}',
                '{^article/(?<id>.*?)/?$}',
            ],
            'Route with a simple suffix'          => [
                'hello[/world]',
                '{^hello(/world)?/?$}',
            ],
            'Route with groups and suffix'        => [
                'article/{id}/with[/title]',
                '{^article/(?<id>.*?)/with(/title)?/?$}',
            ],
            'Complex route'                       => [
                'article/{id:\d+}/with[/{title}]',
                '{^article/(?<id>[0-9]+)/with(/(?<title>.*?))?/?$}',
            ],
        ];

        foreach ($route_to_test as $name => $params) {
            yield $name => $params;
            yield "$name with front /" => ["/$params[0]", $params[1]];
        }
    }

    public function testRouteToRegexNonNamed(): void
    {
        self::assertSame(
            '{^article/.*?/?$}',
            RouteHelper::routeToRegex('article/{id}', false)
        );
    }

    /**
     * @dataProvider provideRegexToCheck
     * @param non-empty-string $regex
     */
    public function testRegexIsValid(string $regex, string $match, bool $should_match): void
    {
        self::assertSame($should_match, matches($match, $regex));
    }

    /**
     * @return iterable<string, array{non-empty-string, string, bool}>
     */
    public static function provideRegexToCheck(): iterable
    {
        try {
            $empty_route  = RouteHelper::routeToRegex('');
            $simple_route = RouteHelper::routeToRegex('hello');
            $group_id     = RouteHelper::routeToRegex('article/{id:\d+}');
            $suffix       = RouteHelper::routeToRegex('hello[/world]');
        } catch (RouterException $e) {
            self::fail($e->getMessage());
        }

        yield 'OK: ""' => [$empty_route, '', true];
        yield 'OK: "" -> /' => [$empty_route, '/', true];
        yield 'NOK: "" -> non-empty' => [$empty_route, 'non-empty', false];

        yield 'OK: hello -> hello' => [$simple_route, 'hello', true];
        yield 'OK: hello -> hello/' => [$simple_route, 'hello/', true];
        yield 'NOK: hello -> something' => [$simple_route, 'something', false];
        yield 'NOK: hello -> /' => [$simple_route, '/', false];

        yield 'OK: article/{id:\d+} -> article/5' => [$group_id, 'article/5', true];
        yield 'OK: article/{id:\d+} -> article/4598' => [$group_id, 'article/4598', true];
        yield 'NOK: article/{id:\d+} -> article/4/hello' => [$group_id, 'article/4/hello', false];
        yield 'NOK: article/{id:\d+} -> article/' => [$group_id, 'article/', false];
        yield 'NOK: article/{id:\d+} -> article/hello' => [$group_id, 'article/hello', false];

        yield 'OK: hello[/world] -> hello' => [$suffix, 'hello', true];
        yield 'OK: hello[/world] -> hello/world' => [$suffix, 'hello/world', true];
        yield 'NOK: hello[/world] -> hello/world!' => [$suffix, 'hello/world!', false];
    }

    public function testRegexCaptureGroups(): void
    {
        $regex   = RouteHelper::routeToRegex('article/{id:\d+}/with[/{title}]');
        $results = [];
        self::assertTrue(preg_match($regex, 'article/2/with/foo', $results) === 1);
        self::assertSame('2', $results['id']);
        self::assertSame('foo', $results['title']);
    }

    public function testItTransformCallableToRequestHandler(): void
    {
        $response = ResponseFactory::build()->withStatus(418)->get();
        $callable = static fn(ServerRequestInterface $request): ResponseInterface => $response; // phpcs:ignore
        $handler  = RouteHelper::callableToRequestHandler($callable);

        self::assertInstanceOf(RequestHandler::class, $handler);
        self::assertSame($response, $handler->handle(ServerRequest::fromGlobals()));
    }

    public function testItTransformCallableToMiddleware(): void
    {
        $request  = new ServerRequest('GET', 'route');
        $callable = static fn(ServerRequestInterface $request): ServerRequestInterface => $request;
        $handler  = RouteHelper::callableToMiddleware($callable);

        self::assertInstanceOf(Middleware::class, $handler);
        self::assertSame($request, $handler->process($request));
    }

    public function testItHasASpecialRuleForMiddlewareRegex(): void
    {
        self::assertSame('{^.*$}', RouteHelper::routeToMiddlewareRegex('*'));
    }
}
