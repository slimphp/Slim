<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Slim\Builder\AppBuilder;
use Slim\Routing\Router;
use Slim\Routing\UrlGenerator;
use UnexpectedValueException;

class UrlGeneratorTest extends TestCase
{
    public function testRelativeUrlFor(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);
        $urlGenerator = new UrlGenerator($router);

        $router->map(['GET'], '/user/{id}', 'user_handler')
            ->setName('user.show');

        // Generate relative URL
        $url = $urlGenerator->relativeUrlFor('user.show', ['id' => 123], ['page' => 2]);

        $this->assertSame('/user/123?page=2', $url);
    }

    public function testUrlFor(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);
        $urlGenerator = new UrlGenerator($router);

        $router->map(['GET'], '/user/{id}', 'user_handler')
            ->setName('user.show');

        $url = $urlGenerator->urlFor('user.show', ['id' => 456], ['sort' => 'asc']);

        $this->assertSame('/user/456?sort=asc', $url);
    }

    public function testFullUrlFor(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);
        $urlGenerator = new UrlGenerator($router);

        $router->map(['GET'], '/user/{id}', 'user_handler')
            ->setName('user.show');

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getScheme')->willReturn('https');
        $uri->method('getAuthority')->willReturn('example.com');

        // Generate full URL
        $fullUrl = $urlGenerator->fullUrlFor($uri, 'user.show', ['id' => 789], ['filter' => 'active']);

        // Check generated full URL
        $this->assertSame('https://example.com/user/789?filter=active', $fullUrl);
    }

    public function testGetNamedRouteThrowsExceptionIfRouteNotFound(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);
        $urlGenerator = new UrlGenerator($router);

        // Attempt to get a non-existent named route
        $urlGenerator->relativeUrlFor('nonexistent.route');
    }

    public function testGetSegmentsThrowsExceptionIfDataIsMissing(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);
        $urlGenerator = new UrlGenerator($router);

        // Define a route with a parameter
        $router->map(['GET'], '/user/{id}', 'user_handler')
            ->setName('user.show');

        $this->expectException(InvalidArgumentException::class);

        // Attempt to generate a URL with missing data for the route parameter
        $urlGenerator->relativeUrlFor('user.show');
    }

    public function testRelativeUrlForWithBasePath(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);
        $router->setBasePath('/api');
        $urlGenerator = new UrlGenerator($router);

        $router->map(['GET'], '/user/{id}', 'user_handler')
            ->setName('user.show');

        // Generate relative URL with base path
        $url = $urlGenerator->relativeUrlFor('user.show', ['id' => 123], ['page' => 2]);

        $this->assertSame('/api/user/123?page=2', $url);
    }
}
