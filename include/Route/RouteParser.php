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
use Archict\Router\Exception\EmptySuffixException;
use Archict\Router\Exception\RouterException;
use Archict\Router\Exception\UnclosedGroupRoutePatternException;
use Archict\Router\Exception\UnclosedSuffixRoutePatternException;

/**
 * @internal
 */
final readonly class RouteParser
{
    public function __construct(private bool $named_groups)
    {
    }

    /**
     * @param string[] $route
     * @return non-empty-string
     * @throws RouterException
     */
    public function parse(array $route): string
    {
        return '{^' . $this->entrypoint($route) . '/?$}';
    }

    /**
     * @param string[] $route
     * @throws RouterException
     */
    private function entrypoint(array $route): string
    {
        if ($route === []) {
            return '';
        }

        return match ($route[0]) {
            '{'     => $this->parseGroup(array_slice($route, 1)),
            '['     => $this->parseSuffix(array_slice($route, 1)),
            default => $route[0] . $this->entrypoint(array_slice($route, 1)),
        };
    }

    /**
     * @param string[] $route
     * @throws RouterException
     */
    private function parseGroup(array $route): string
    {
        $group = '';
        while ($route !== [] && $route[0] !== '}') {
            $group .= $route[0];

            $route = array_slice($route, 1);
        }

        if ($route === []) {
            throw new UnclosedGroupRoutePatternException();
        }

        if ($group === '') {
            throw new EmptyGroupNameException();
        }

        $parts = explode(':', $group);
        $name  = $parts[0];
        if (count($parts) === 1) {
            $rule = '.*?';
        } else {
            $group_rule = implode(array_slice($parts, 1));
            $rule       = match ($group_rule) {
                '\d+'   => '[0-9]+',
                '\l+'   => '[a-zA-Z]+',
                '\a+'   => '[a-zA-Z0-9]+',
                '\s+'   => '[a-zA-Z0-9_]+',
                ''      => '.*?',
                default => $group_rule,
            };
        }

        if ($this->named_groups) {
            $result = "(?<$name>$rule)";
        } else {
            $result = $rule;
        }

        return $result . $this->entrypoint(array_slice($route, 1));
    }

    /**
     * @param string[] $route
     * @throws RouterException
     */
    private function parseSuffix(array $route): string
    {
        if ($route === []) {
            throw new UnclosedSuffixRoutePatternException();
        }

        $suffix = '';
        while (count($route) > 1) {
            $suffix .= $route[0];

            $route = array_slice($route, 1);
        }

        assert(count($route) === 1);
        if ($route[0] !== ']') {
            throw new UnclosedSuffixRoutePatternException();
        }

        if ($suffix === '') {
            throw new EmptySuffixException();
        }

        return '(' . $this->entrypoint(mb_str_split($suffix)) . ')?';
    }
}
