<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Http\Message\ResponseInterface;

/**
 * Response Emitter Class
 *
 * @package Slim
 * @since 4.0.0
 */
class ResponseEmitter
{
    /**
     * @var int
     */
    private $responseChunkSize;

    /**
     * ResponseEmitter constructor.
     * @param int $responseChunkSize
     */
    public function __construct(int $responseChunkSize = 4096)
    {
        $this->responseChunkSize = $responseChunkSize;
    }

    /**
     * Asserts response body is empty or status code is 204, 205 or 304
     *
     * @param ResponseInterface $response
     * @return bool
     */
    public function isResponseEmpty(ResponseInterface $response): bool
    {
        $contents = (string) $response->getBody();
        return empty($contents) || in_array($response->getStatusCode(), [204, 205, 304]);
    }

    /**
     * Send the response the client
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response): void
    {
        if (!headers_sent()) {
            if ($this->isResponseEmpty($response)) {
                $response = $response
                    ->withoutHeader('Content-Type')
                    ->withoutHeader('Content-Length');
            }
            $this->emitHeaders($response);
            $this->emitStatusLine($response);
        }

        if (!$this->isResponseEmpty($response)) {
            $this->emitBody($response);
        }
    }

    /**
     * Emit Response Headers
     *
     * @param ResponseInterface $response
     */
    private function emitHeaders(ResponseInterface $response): void
    {
        foreach ($response->getHeaders() as $name => $values) {
            $first = $name !== 'Set-Cookie';
            foreach ($values as $value) {
                $header = sprintf('%s: %s', $name, $value);
                header($header, $first);
                $first = false;
            }
        }
    }

    /**
     * Emit Status Line
     *
     * @param ResponseInterface $response
     */
    private function emitStatusLine(ResponseInterface $response): void
    {
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());
    }

    /**
     * Emit Body
     *
     * @param ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response): void
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $amountToRead = (int) $response->getHeaderLine('Content-Length');
        if (!$amountToRead) {
            $amountToRead = $body->getSize();
        }

        if ($amountToRead) {
            while ($amountToRead > 0 && !$body->eof()) {
                $length = min($this->responseChunkSize, $amountToRead);
                $data = $body->read($length);
                echo $data;

                $amountToRead -= strlen($data);

                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        } else {
            while (!$body->eof()) {
                echo $body->read($this->responseChunkSize);
                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }
}
