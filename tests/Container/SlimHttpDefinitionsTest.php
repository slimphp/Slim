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
use ReflectionClass;
use RuntimeException;
use Slim\Container\SlimHttpDefinitions;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\Factory\DecoratedUriFactory;
use Slim\Interfaces\ServerRequestCreatorInterface;

class SlimHttpDefinitionsTest extends TestCase
{
    public function testInvokeReturnsCorrectDefinitions()
    {
        $definitions = (new SlimHttpDefinitions())->__invoke();
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
        $definitions = (new SlimHttpDefinitions())->__invoke();

        $container = new Container($definitions);
        $serverRequestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $serverRequestFactory);

        $serverRequest = $serverRequestFactory->createServerRequest('GET', 'https://example.com');
        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
    }

    public function testServerRequestCreatorInterface()
    {
        $definitions = (new SlimHttpDefinitions())->__invoke();

        $container = new Container($definitions);
        $serverRequestCreator = $container->get(ServerRequestCreatorInterface::class);

        $this->assertInstanceOf(ServerRequestCreatorInterface::class, $serverRequestCreator);
        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequestCreator->createServerRequestFromGlobals());
    }

    public function testResponseFactoryInterface()
    {
        $definitions = (new SlimHttpDefinitions())->__invoke();

        $container = new Container($definitions);
        $responseFactory = $container->get(ResponseFactoryInterface::class);

        $this->assertInstanceOf(ResponseFactoryInterface::class, $responseFactory);

        if ($responseFactory instanceof DecoratedResponseFactory) {
            $this->assertInstanceOf(DecoratedResponseFactory::class, $responseFactory);
        }
    }

    public function testStreamFactoryInterface()
    {
        $definitions = (new SlimHttpDefinitions())->__invoke();

        $container = new Container($definitions);
        $streamFactory = $container->get(StreamFactoryInterface::class);

        $this->assertInstanceOf(StreamFactoryInterface::class, $streamFactory);
    }

    public function testUriFactoryInterface()
    {
        $definitions = (new SlimHttpDefinitions())->__invoke();

        $container = new Container($definitions);
        $uriFactory = $container->get(UriFactoryInterface::class);

        $this->assertInstanceOf(UriFactoryInterface::class, $uriFactory);

        if ($uriFactory instanceof DecoratedUriFactory) {
            $this->assertInstanceOf(DecoratedUriFactory::class, $uriFactory);
        }
    }

    public function testUploadedFileFactoryInterface()
    {
        $definitions = (new SlimHttpDefinitions())->__invoke();

        $container = new Container($definitions);
        $uploadedFileFactory = $container->get(UploadedFileFactoryInterface::class);

        $this->assertInstanceOf(UploadedFileFactoryInterface::class, $uploadedFileFactory);
    }

    public function testResponseFactoryInterfaceThrowsRuntimeExceptionWhenNoImplementationIsAvailable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not detect any PSR-17 ResponseFactory implementations.');

        $definitions = new SlimHttpDefinitions();

        // Use reflection to inject the mock callable into the $classExists property
        $reflection = new ReflectionClass($definitions);
        $classExistsProperty = $reflection->getProperty('classExists');
        $classExistsProperty->setAccessible(true);
        $classExistsProperty->setValue($definitions, fn () => false);

        $container = new Container($definitions());
        $container->get(ResponseFactoryInterface::class);
    }

    public function testStreamFactoryInterfaceThrowsRuntimeExceptionWhenNoImplementationIsAvailable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not instantiate a StreamFactory.');

        $definitions = new SlimHttpDefinitions();

        // Use reflection to inject the mock callable into the $classExists property
        $reflection = new ReflectionClass($definitions);
        $classExistsProperty = $reflection->getProperty('classExists');
        $classExistsProperty->setAccessible(true);
        $classExistsProperty->setValue($definitions, fn () => false);

        $container = new Container($definitions());
        $container->get(StreamFactoryInterface::class);
    }

    public function testUriFactoryInterfaceThrowsRuntimeExceptionWhenNoImplementationIsAvailable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not instantiate a UriFactory.');

        $definitions = new SlimHttpDefinitions();

        // Use reflection to inject the mock callable into the $classExists property
        $reflection = new ReflectionClass($definitions);
        $classExistsProperty = $reflection->getProperty('classExists');
        $classExistsProperty->setAccessible(true);
        $classExistsProperty->setValue($definitions, fn () => false);

        $container = new Container($definitions());
        $container->get(UriFactoryInterface::class);
    }

    public function testUploadedFileFactoryInterfaceThrowsRuntimeExceptionWhenNoImplementationIsAvailable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not instantiate a UploadedFileFactory.');

        $definitions = new SlimHttpDefinitions();

        // Use reflection to inject the mock callable into the $classExists property
        $reflection = new ReflectionClass($definitions);
        $classExistsProperty = $reflection->getProperty('classExists');
        $classExistsProperty->setAccessible(true);
        $classExistsProperty->setValue($definitions, fn () => false);

        $container = new Container($definitions());
        $container->get(UploadedFileFactoryInterface::class);
    }
}
