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
use Slim\Builder\AppBuilder;
use Slim\Container\GuzzleDefinitions;
use Slim\Container\HttpDefinitions;
use Slim\Container\HttpSoftDefinitions;
use Slim\Container\LaminasDiactorosDefinitions;
use Slim\Container\NyholmDefinitions;
use Slim\Container\SlimHttpDefinitions;
use Slim\Container\SlimPsr7Definitions;
use Slim\Emitter\ResponseEmitter;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\Routing\Router;
use Slim\Routing\Strategies\RequestResponse;

final class DefaultDefinitionsTest extends TestCase
{
    public function testApp(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $app = $container->get(App::class);

        $this->assertInstanceOf(App::class, $app);
    }

    public function testContainerResolverInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $resolver = $container->get(ContainerResolverInterface::class);

        $this->assertInstanceOf(ContainerResolverInterface::class, $resolver);
    }

    public function testRequestHandlerInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $requestHandler = $container->get(RequestHandlerInterface::class);

        $this->assertInstanceOf(RequestHandlerInterface::class, $requestHandler);
        $this->assertInstanceOf(MiddlewareRequestHandler::class, $requestHandler);
    }

    public function testServerRequestFactoryInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $requestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $requestFactory);
    }

    #[DataProvider('serverRequestFactoryDefinitionsProvider')]
    public function testServerRequestFactoryInterfaceWithDefinitions(callable $definition, string $instanceOf): void
    {
        $definitions = call_user_func(new HttpDefinitions());
        $definitions = array_merge($definitions, call_user_func($definition));

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
            'LaminasDiactorosDefinitions' => [new LaminasDiactorosDefinitions(), LaminasServerRequestFactory::class],
            'NyholmDefinitions' => [new NyholmDefinitions(), Psr17Factory::class],
            'SlimHttpDefinitions' => [new SlimHttpDefinitions(), ServerRequestFactoryInterface::class],
            'SlimPsr7Definitions' => [new SlimPsr7Definitions(), ServerRequestFactory::class],
        ];
    }

    public function testResponseFactoryInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $responseFactory = $container->get(ResponseFactoryInterface::class);

        $this->assertInstanceOf(ResponseFactoryInterface::class, $responseFactory);
    }

    public function testStreamFactoryInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $streamFactory = $container->get(StreamFactoryInterface::class);

        $this->assertInstanceOf(StreamFactoryInterface::class, $streamFactory);
    }

    public function testUriFactoryInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $uriFactory = $container->get(UriFactoryInterface::class);

        $this->assertInstanceOf(UriFactoryInterface::class, $uriFactory);
    }

    public function testUploadedFileFactoryInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $uploadedFileFactory = $container->get(UploadedFileFactoryInterface::class);

        $this->assertInstanceOf(UploadedFileFactoryInterface::class, $uploadedFileFactory);
    }

    public function testEmitterInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $emitter = $container->get(EmitterInterface::class);

        $this->assertInstanceOf(ResponseEmitter::class, $emitter);
    }

    public function testRouter(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $router = $container->get(Router::class);

        $this->assertInstanceOf(Router::class, $router);
    }

    public function testRequestHandlerInvocationStrategyInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $invocationStrategy = $container->get(RequestHandlerInvocationStrategyInterface::class);

        $this->assertInstanceOf(RequestResponse::class, $invocationStrategy);
    }

    public function testExceptionHandlingMiddleware(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $exceptionHandlingMiddleware = $container->get(ExceptionHandlingMiddleware::class);

        $this->assertInstanceOf(ExceptionHandlingMiddleware::class, $exceptionHandlingMiddleware);
    }

    public function testLoggerInterface(): void
    {
        $container = (new AppBuilder())->build()->getContainer();
        $logger = $container->get(LoggerInterface::class);

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }
}
