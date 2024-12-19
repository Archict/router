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
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class RouteInformationTest extends TestCase
{
    public function testItBuildsADefaultHandlerForHEAD(): void
    {
        $info = RouteInformation::buildDefaultHEAD('/test');
        self::assertSame(Method::HEAD, $info->method);
        self::assertSame('/test', $info->route);
        self::assertSame('', $info->handler->handle(new ServerRequest('HEAD', '/test')));
    }

    public function testItBuildsADefaultHandlerForOPTIONS(): void
    {
        $info = RouteInformation::buildDefaultOPTIONS('/test', [Method::GET, Method::POST]);
        self::assertSame(Method::OPTIONS, $info->method);
        self::assertSame('/test', $info->route);
        $response = $info->handler->handle(new ServerRequest('OPTIONS', '/test'));
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame('', $response->getBody()->getContents());
        self::assertSame('GET, POST, OPTIONS, HEAD', $response->getHeaderLine('Allow'));
    }
}
