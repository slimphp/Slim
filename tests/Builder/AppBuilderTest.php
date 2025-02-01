<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Builder;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Container\DefaultDefinitions;
use Slim\Container\HttpDefinitions;
use Slim\Interfaces\ContainerFactoryInterface;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class AppBuilderTest extends TestCase
{
    use AppTestTrait;

    public function testAddDefinitionsClass(): void
    {
        $builder = new AppBuilder();
        $class = new class {
            public function __invoke()
            {
                return ['foo' => 'bar'];
            }
        };
        $builder->addDefinitionsClass($class::class);
        $app = $builder->build();

        $this->assertSame('bar', $app->getContainer()->get('foo'));
    }

    public function testAddDefinitionsClassException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Definition file should return an array of definitions');

        $builder = new AppBuilder();
        $class = new class {
            public function __invoke()
            {
                return null;
            }
        };
        $builder->addDefinitionsClass($class::class);
        $app = $builder->build();

        $this->assertSame('bar', $app->getContainer()->get('foo'));
    }

    public function testAddDefinitionsFile(): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitionsFile(__DIR__ . '/TestContainerDefinition.php');
        $app = $builder->build();

        $this->assertSame('bar', $app->getContainer()->get('foo'));
    }

    public function testAddDefinitionsFileError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Definition file should return an array of definitions');

        $builder = new AppBuilder();
        $builder->addDefinitionsFile(__DIR__ . '/TestContainerError.php');
        $app = $builder->build();

        $this->assertSame('bar', $app->getContainer()->get('foo'));
    }

    public function testSetContainerFactory(): void
    {
        $builder = new AppBuilder();
        $builder->setContainerFactory(
            new class implements ContainerFactoryInterface {
                public function createContainer(array $definitions = []): ContainerInterface
                {
                    $defaults = (new DefaultDefinitions())->__invoke();
                    $defaults = array_merge($defaults, (new HttpDefinitions())->__invoke());

                    $defaults['foo'] = 'bar';

                    return new Container($defaults);
                }
            }
        );
        $app = $builder->build();
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write($this->get('foo'));

            return $response;
        });

        $response = $app->handle($request);
        $this->assertSame('bar', (string)$response->getBody());
    }

    public function testMiddlewareOrderFifo(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('OK');

            return $response;
        });

        $response = $app->handle($request);
        $this->assertSame('OK', (string)$response->getBody());
    }
}
