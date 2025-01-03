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

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use function Psl\Json\encode as psl_json_encode;

final readonly class ResponseFactory
{
    private function __construct(
        private ResponseInterface $response,
        private HttpFactory $factory,
    ) {
    }

    public static function build(): self
    {
        $factory = new HttpFactory();

        return new self($factory->createResponse(), $factory);
    }

    public function get(): ResponseInterface
    {
        return $this->response;
    }

    public function withStatus(int $code): self
    {
        return new self($this->response->withStatus($code), $this->factory);
    }

    /**
     * @param string|string[] $value
     */
    public function withHeader(string $name, string|array $value): self
    {
        return new self($this->response->withHeader($name, $value), $this->factory);
    }

    public function withBody(string $body): self
    {
        return new self($this->response->withBody($this->factory->createStream($body)), $this->factory);
    }

    /**
     * Response with JSON format
     */
    public function json(string|array $json): self // @phpstan-ignore-line
    {
        return $this
            ->withHeader('Content-Type', 'application/json')
            ->withBody(is_string($json) ? $json : psl_json_encode($json));
    }

    /**
     * Response with XML format
     */
    public function xml(string|SimpleXMLElement $xml): self
    {
        return $this
            ->withHeader('Content-Type', 'application/xml')
            ->withBody(is_string($xml) ? $xml : (string) $xml->asXML());
    }

    /**
     * HTTP 301
     * Location: $uri
     */
    public function redirect(string $uri): self
    {
        return $this
            ->withStatus(301)
            ->withHeader('Location', $uri);
    }
}
