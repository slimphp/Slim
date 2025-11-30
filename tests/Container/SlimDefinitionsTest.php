<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Container;

use DI\Container;
use GuzzleHttp\Psr7\HttpFactory;
use HttpSoft\Message\ServerRequestFactory as HttpSoftServerRequestFactory;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Container\Definition\GuzzleDefinitions;
use Slim\Container\Definition\HttpDefinitions;
use Slim\Container\Definition\HttpSoftDefinitions;
use Slim\Container\Definition\LaminasDefinitions;
use Slim\Container\Definition\NyholmDefinitions;
use Slim\Container\Definition\SlimHttpDefinitions;
use Slim\Container\Definition\SlimPsr7Definitions;
use Slim\Emitter\ResponseEmitter;
use Slim\Factory\AppFactory;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Middleware\RoutingMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SlimDefinitionsTest extends TestCase
{
    public function testApp(): void
    {
        $container = AppFactory::create()->getContainer();
        $app = $container->get(App::class);

        $this->assertInstanceOf(App::class, $app);
    }

    public function testContainerResolverInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $resolver = $container->get(ContainerResolverInterface::class);

        $this->assertInstanceOf(ContainerResolverInterface::class, $resolver);
    }

    public function testRequestHandlerInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $requestHandler = $container->get(RequestHandlerInterface::class);

        $this->assertInstanceOf(RequestHandlerInterface::class, $requestHandler);
    }

    public function testServerRequestFactoryInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $requestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $requestFactory);
    }

    #[DataProvider('serverRequestFactoryDefinitionsProvider')]
    public function testServerRequestFactoryInterfaceWithDefinitions($definition, string $instanceOf): void
    {
        $definitions = (new HttpDefinitions())->getDefinitions();
        $definitions = array_merge($definitions, (new $definition())->getDefinitions());

        $container = new Container($definitions);
        $requestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $requestFactory);
        $this->assertInstanceOf($instanceOf, $requestFactory);
    }

    public static function serverRequestFactoryDefinitionsProvider(): array
    {
        return [
            'GuzzleDefinitions' => [new GuzzleDefinitions(), HttpFactory::class],
            'HttpSoftDefinitions' => [new HttpSoftDefinitions(), HttpSoftServerRequestFactory::class],
            'LaminasDiactorosDefinitions' => [new LaminasDefinitions(), LaminasServerRequestFactory::class],
            'NyholmDefinitions' => [new NyholmDefinitions(), Psr17Factory::class],
            'SlimHttpDefinitions' => [new SlimHttpDefinitions(), ServerRequestFactoryInterface::class],
            'SlimPsr7Definitions' => [new SlimPsr7Definitions(), ServerRequestFactory::class],
        ];
    }

    public function testResponseFactoryInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $responseFactory = $container->get(ResponseFactoryInterface::class);

        $this->assertInstanceOf(ResponseFactoryInterface::class, $responseFactory);
    }

    public function testStreamFactoryInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $streamFactory = $container->get(StreamFactoryInterface::class);

        $this->assertInstanceOf(StreamFactoryInterface::class, $streamFactory);
    }

    public function testUriFactoryInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $uriFactory = $container->get(UriFactoryInterface::class);

        $this->assertInstanceOf(UriFactoryInterface::class, $uriFactory);
    }

    public function testUploadedFileFactoryInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $uploadedFileFactory = $container->get(UploadedFileFactoryInterface::class);

        $this->assertInstanceOf(UploadedFileFactoryInterface::class, $uploadedFileFactory);
    }

    public function testEmitterInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $emitter = $container->get(EmitterInterface::class);

        $this->assertInstanceOf(ResponseEmitter::class, $emitter);
    }

    public function testRouter(): void
    {
        $container = AppFactory::create()->getContainer();
        $router = $container->get(RoutingMiddleware::class);

        $this->assertInstanceOf(RoutingMiddleware::class, $router);
    }

    public function testRequestHandlerInvocationStrategyInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $invocationStrategy = $container->get(RequestHandlerInvocationStrategyInterface::class);

        $this->assertInstanceOf(RequestHandlerInvocationStrategyInterface::class, $invocationStrategy);
    }

    public function testLoggerInterface(): void
    {
        $container = AppFactory::create()->getContainer();
        $logger = $container->get(LoggerInterface::class);

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }
}
