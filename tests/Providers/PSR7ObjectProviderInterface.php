<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Providers;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Interface PSR7ObjectProviderInterface
 *
 * @package Slim\Tests\Providers
 */
interface PSR7ObjectProviderInterface
{
    /**
     * @return ServerRequestFactoryInterface
     */
    public function getServerRequestFactory(): ServerRequestFactoryInterface;

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface;

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface;

    /**
     * @param string $uri
     * @param string $method
     * @param array  $data
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $uri, string $method = 'GET', array $data = []): ServerRequestInterface;

    /**
     * @param int    $statusCode
     * @param string $reasonPhrase
     * @return ResponseInterface
     */
    public function createResponse(int $statusCode = 200, string $reasonPhrase = ''): ResponseInterface;

    /**
     * @param string $contents
     * @return StreamInterface
     */
    public function createStream(string $contents = ''): StreamInterface;
}
