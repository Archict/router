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

use Archict\Router\Exception\MethodNotAllowedException;

enum Method: string
{
    case GET     = 'GET';
    case HEAD    = 'HEAD';
    case POST    = 'POST';
    case PUT     = 'PUT';
    case DELETE  = 'DELETE';
    case CONNECT = 'CONNECT';
    case OPTIONS = 'OPTIONS';
    case TRACE   = 'TRACE';
    case PATCH   = 'PATCH';

    case ALL = '*';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @throws MethodNotAllowedException
     */
    public static function fromString(string $value): self
    {
        return match ($value) {
            'GET'     => self::GET,
            'HEAD'    => self::HEAD,
            'POST'    => self::POST,
            'PUT'     => self::PUT,
            'DELETE'  => self::DELETE,
            'CONNECT' => self::CONNECT,
            'OPTIONS' => self::OPTIONS,
            'TRACE'   => self::TRACE,
            'PATCH'   => self::PATCH,
            '*'       => self::ALL,
            default   => throw new MethodNotAllowedException($value),
        };
    }
}
