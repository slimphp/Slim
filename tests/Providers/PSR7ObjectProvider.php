<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Providers;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class PSR7ObjectProvider
 * @package Slim\Tests\Providers
 */
class PSR7ObjectProvider implements PSR7ObjectProviderInterface
{
    /**
     * @return ServerRequestFactoryInterface
     */
    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return new Psr17Factory();
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }

    /**
     * @param string $uri
     * @param string $method
     * @param array $data
     * @return ServerRequestInterface
     */
    public function createServerRequest(
        string $uri,
        string $method = 'GET',
        array $data = []
    ): ServerRequestInterface {
        $headers = array_merge([
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => $method,
            'SCRIPT_NAME'          => '/index.php',
            'REQUEST_URI'          => '',
            'QUERY_STRING'         => '',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'Slim Framework',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REQUEST_TIME'         => time(),
            'REQUEST_TIME_FLOAT'   => microtime(true),
        ], $data);

        return $this
            ->getServerRequestFactory()
            ->createServerRequest($method, $uri, $headers);
    }

    /**
     * @param int $statusCode
     * @param string $reasonPhrase
     * @return ResponseInterface
     */
    public function createResponse(int $statusCode = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this
            ->getResponseFactory()
            ->createResponse($statusCode, $reasonPhrase);
    }

    /**
     * @param string $contents
     * @return StreamInterface
     */
    public function createStream(string $contents = ''): StreamInterface
    {
        return $this
            ->getStreamFactory()
            ->createStream($contents);
    }
}
