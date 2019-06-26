<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Slim\CallableResolver;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Tests\Providers\PSR7ObjectProvider;

abstract class TestCase extends PhpUnitTestCase
{
    /**
     * @return ServerRequestFactoryInterface
     */
    protected function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        return $psr7ObjectProvider->getServerRequestFactory();
    }

    /**
     * @return ResponseFactoryInterface
     */
    protected function getResponseFactory(): ResponseFactoryInterface
    {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        return $psr7ObjectProvider->getResponseFactory();
    }

    /**
     * @return StreamFactoryInterface
     */
    protected function getStreamFactory(): StreamFactoryInterface
    {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        return $psr7ObjectProvider->getStreamFactory();
    }

    /**
     * @param ContainerInterface|null $container
     *
     * @return CallableResolverInterface
     */
    protected function getCallableResolver(?ContainerInterface $container = null): CallableResolverInterface
    {
        return new CallableResolver($container);
    }

    /**
     * @param string $uri
     * @param string $method
     * @param array  $data
     * @return ServerRequestInterface
     */
    protected function createServerRequest(
        string $uri,
        string $method = 'GET',
        array $data = []
    ): ServerRequestInterface {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        return $psr7ObjectProvider->createServerRequest($uri, $method, $data);
    }

    /**
     * @param int    $statusCode
     * @param string $reasonPhrase
     * @return ResponseInterface
     */
    protected function createResponse(int $statusCode = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        return $psr7ObjectProvider->createResponse($statusCode, $reasonPhrase);
    }

    /**
     * @param string $contents
     * @return StreamInterface
     */
    protected function createStream(string $contents = ''): StreamInterface
    {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        return $psr7ObjectProvider->createStream($contents);
    }
}
