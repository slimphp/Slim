<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Tests\Traits\AppTestTrait;

final class HttpExceptionTest extends TestCase
{
    use AppTestTrait;

    public function testHttpExceptionRequestResponseGetterSetters(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $exception = new HttpNotFoundException($request);

        $this->assertInstanceOf(ServerRequestInterface::class, $exception->getRequest());
    }

    public function testHttpExceptionAttributeGettersSetters(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $exception = new HttpNotFoundException($request);
        $exception->setTitle('Title');
        $exception->setDescription('Description');

        $this->assertSame('Title', $exception->getTitle());
        $this->assertSame('Description', $exception->getDescription());
    }

    public function testHttpNotAllowedExceptionGetAllowedMethods(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['GET']);
        $this->assertSame(['GET'], $exception->getAllowedMethods());
        $this->assertSame('Method not allowed.', $exception->getMessage());

        $exception = new HttpMethodNotAllowedException($request);
        $this->assertSame([], $exception->getAllowedMethods());
        $this->assertSame('Method not allowed.', $exception->getMessage());
    }
}
