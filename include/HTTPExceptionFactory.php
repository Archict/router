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

use Archict\Router\Exception\HTTP\HTTPException;
use Psr\Http\Message\ResponseInterface;

final class HTTPExceptionFactory
{
    private function __construct()
    {
    }

    public static function BadRequest(string $body = ''): HTTPException
    {
        return self::custom(400, '', $body);
    }

    public static function Forbidden(string $body = ''): HTTPException
    {
        return self::custom(403, '', $body);
    }

    public static function NotFound(string $body = ''): HTTPException
    {
        return self::custom(404, '', $body);
    }

    public static function ServerError(string $body = ''): HTTPException
    {
        return self::custom(500, '', $body);
    }

    public static function custom(int $code, string $reason, string $body = ''): HTTPException
    {
        return new class($code, $reason, $body) extends HTTPException {
            public function __construct(
                private readonly int $status_code,
                private readonly string $reason,
                private readonly string $body,
            ) {
                parent::__construct();
            }

            public function toResponse(): ResponseInterface
            {
                return $this->factory
                    ->createResponse($this->status_code, $this->reason)
                    ->withBody($this->factory->createStream($this->body));
            }
        };
    }
}
