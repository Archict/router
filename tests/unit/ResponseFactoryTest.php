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

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class ResponseFactoryTest extends TestCase
{
    public function testItCreatesADefaultResponse(): void
    {
        $response = ResponseFactory::build()->get();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('OK', $response->getReasonPhrase());
    }

    public function testItCreatesAResponseWithStatusCode(): void
    {
        $response = ResponseFactory::build()->withStatus(418)->get();

        self::assertEquals(418, $response->getStatusCode());
        self::assertEquals("I'm a teapot", $response->getReasonPhrase());
    }

    public function testItCreateAResponseWithBody(): void
    {
        $response = ResponseFactory::build()->withBody('Hello World!')->get();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Hello World!', $response->getBody()->getContents());
    }

    public function testItCanHaveManyHeaders(): void
    {
        $response = ResponseFactory::build()
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('Accept-Encoding', 'gzip, deflate')
            ->withHeader('Accept-Language', 'en-US')
            ->withHeader('Content-Language', 'en-US')
            ->get();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEqualsCanonicalizing(
            [
                'Content-Type'     => ['application/json'],
                'Accept'           => ['application/json'],
                'Accept-Encoding'  => ['gzip, deflate'],
                'Accept-Language'  => ['en-US'],
                'Content-Language' => ['en-US'],
            ],
            $response->getHeaders()
        );
    }

    public function testShortcutJSON(): void
    {
        $response = ResponseFactory::build()->json(['hello' => 'world'])->get();

        self::assertTrue($response->hasHeader('Content-Type'));
        self::assertSame(['application/json'], $response->getHeader('Content-Type'));
        self::assertSame('{"hello":"world"}', $response->getBody()->getContents());
    }

    public function testShortcutXML(): void
    {
        $xml      = new SimpleXMLElement('<a></a>');
        $response = ResponseFactory::build()->xml($xml)->get();

        self::assertTrue($response->hasHeader('Content-Type'));
        self::assertSame(['application/xml'], $response->getHeader('Content-Type'));
        self::assertSame($xml->asXML(), $response->getBody()->getContents());
    }

    public function testShortcutRedirect(): void
    {
        $response = ResponseFactory::build()->redirect('/foo')->get();

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/foo', $response->getHeaderLine('Location'));
    }
}
