<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Class Test
 * @package Slim\Tests
 */
abstract class Test extends TestCase
{
    /**
     * @return ServerRequestFactoryInterface
     */
    protected function serverRequestFactory(): ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }

    /**
     * @return ResponseFactoryInterface
     */
    protected function responseFactory(): ResponseFactoryInterface
    {
        return new Psr17Factory();
    }

    /**
     * @return StreamFactoryInterface
     */
    protected function streamFactory(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }

    /**
     * @param string $uri
     * @param string $method
     * @param array $data
     * @return ServerRequestInterface
     */
    protected function createServerRequest(
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
            ->serverRequestFactory()
            ->createServerRequest($method, $uri, $headers);
    }

    /**
     * @param int $statusCode
     * @param string $reasonPhrase
     * @return ResponseInterface
     */
    protected function createResponse(int $statusCode = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this
            ->responseFactory()
            ->createResponse($statusCode, $reasonPhrase);
    }
}
