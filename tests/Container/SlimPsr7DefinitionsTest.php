<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Container;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\Container\Definition\SlimPsr7Definitions;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Slim\Psr7\Factory\UriFactory;

class SlimPsr7DefinitionsTest extends TestCase
{
    public function testInvokeReturnsCorrectDefinitions()
    {
        $definitions = (new SlimPsr7Definitions())->getDefinitions();
        $container = new Container($definitions);

        $this->assertTrue($container->has(ServerRequestFactoryInterface::class));
        $this->assertTrue($container->has(ServerRequestCreatorInterface::class));
        $this->assertTrue($container->has(ResponseFactoryInterface::class));
        $this->assertTrue($container->has(StreamFactoryInterface::class));
        $this->assertTrue($container->has(UriFactoryInterface::class));
        $this->assertTrue($container->has(UploadedFileFactoryInterface::class));
    }

    public function testServerRequestFactoryInterface()
    {
        $definitions = (new SlimPsr7Definitions())->getDefinitions();

        $container = new Container($definitions);
        $serverRequestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactory::class, $serverRequestFactory);
    }

    public function testServerRequestCreatorInterface()
    {
        $definitions = (new SlimPsr7Definitions())->getDefinitions();

        $container = new Container($definitions);
        $serverRequestCreator = $container->get(ServerRequestCreatorInterface::class);

        $this->assertInstanceOf(ServerRequestCreatorInterface::class, $serverRequestCreator);
        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequestCreator->createServerRequestFromGlobals());
    }

    public function testResponseFactoryInterface()
    {
        $definitions = (new SlimPsr7Definitions())->getDefinitions();

        $container = new Container($definitions);
        $responseFactory = $container->get(ResponseFactoryInterface::class);

        $this->assertInstanceOf(ResponseFactory::class, $responseFactory);
    }

    public function testStreamFactoryInterface()
    {
        $definitions = (new SlimPsr7Definitions())->getDefinitions();
        $container = new Container($definitions);
        $streamFactory = $container->get(StreamFactoryInterface::class);

        $this->assertInstanceOf(StreamFactory::class, $streamFactory);
    }

    public function testUriFactoryInterface()
    {
        $definitions = (new SlimPsr7Definitions())->getDefinitions();

        $container = new Container($definitions);
        $uriFactory = $container->get(UriFactoryInterface::class);

        $this->assertInstanceOf(UriFactory::class, $uriFactory);
    }

    public function testUploadedFileFactoryInterface()
    {
        $definitions = (new SlimPsr7Definitions())->getDefinitions();

        $container = new Container($definitions);
        $uploadedFileFactory = $container->get(UploadedFileFactoryInterface::class);

        $this->assertInstanceOf(UploadedFileFactory::class, $uploadedFileFactory);
    }
}
