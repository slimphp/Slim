<?php

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Middleware\TrustedProxyMiddleware;

class TrustedProxyMiddlewareTest extends TestCase
{
    public function testNoOpWhenNoTrustedProxiesConfigured(): void
    {
        $captured = $this->runWith(
            new TrustedProxyMiddleware(),
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-For' => '1.2.3.4'],
        );

        $this->assertNull($captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
    }

    public function testIgnoresForwardedHeadersFromUntrustedPeer(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '203.0.113.99'],
            [
                'X-Forwarded-For' => '1.2.3.4',
                'X-Forwarded-Proto' => 'https',
            ],
        );

        $this->assertNull($captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
        $this->assertSame('http', $captured->getUri()->getScheme());
    }

    public function testResolvesClientIpFromXForwardedFor(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-For' => '203.0.113.42'],
        );

        $this->assertSame('203.0.113.42', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
    }

    public function testWalksChainPastTrustedHops(): void
    {
        $middleware = (new TrustedProxyMiddleware())
            ->withTrustedProxies(['10.0.0.0/8']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-For' => '203.0.113.42, 10.0.0.1, 10.0.0.2'],
        );

        $this->assertSame('203.0.113.42', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
    }

    public function testIpv4CidrMatching(): void
    {
        $middleware = (new TrustedProxyMiddleware())
            ->withTrustedProxies(['172.16.0.0/12']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '172.20.5.99'],
            ['X-Forwarded-For' => '198.51.100.7'],
        );

        $this->assertSame('198.51.100.7', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
    }

    public function testIpv6CidrMatching(): void
    {
        $middleware = (new TrustedProxyMiddleware())
            ->withTrustedProxies(['2001:db8::/32']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '2001:db8:1::5'],
            ['X-Forwarded-For' => '198.51.100.7'],
        );

        $this->assertSame('198.51.100.7', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
    }

    public function testRewritesSchemeFromXForwardedProto(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-Proto' => 'https'],
        );

        $this->assertSame('https', $captured->getUri()->getScheme());
    }

    public function testRewritesHostFromXForwardedHost(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-Host' => 'api.example.com'],
        );

        $this->assertSame('api.example.com', $captured->getUri()->getHost());
    }

    public function testRewritesPortFromXForwardedPort(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-Port' => '8443'],
        );

        $this->assertSame(8443, $captured->getUri()->getPort());
    }

    public function testForwardedHostMayIncludePort(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-Host' => 'api.example.com:8443'],
        );

        $this->assertSame('api.example.com', $captured->getUri()->getHost());
        $this->assertSame(8443, $captured->getUri()->getPort());
    }

    public function testRfc7239ForwardedHeader(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['Forwarded' => 'for=203.0.113.42;proto=https;host=api.example.com'],
        );

        $this->assertSame('203.0.113.42', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
        $this->assertSame('https', $captured->getUri()->getScheme());
        $this->assertSame('api.example.com', $captured->getUri()->getHost());
    }

    public function testRfc7239IpV6ForAddress(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['Forwarded' => 'for="[2001:db8::1]:4711";proto=https'],
        );

        $this->assertSame('2001:db8::1', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
    }

    public function testRfc7239TakesPrecedenceOverXForwardedFor(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            [
                'Forwarded' => 'for=203.0.113.42',
                'X-Forwarded-For' => '198.51.100.7',
            ],
        );

        $this->assertSame('203.0.113.42', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
    }

    public function testUntrustedHeadersAreIgnored(): void
    {
        $middleware = (new TrustedProxyMiddleware())
            ->withTrustedProxies(['10.0.0.5'])
            ->withTrustedHeaders([]);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            [
                'X-Forwarded-For' => '203.0.113.42',
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'api.example.com',
            ],
        );

        $this->assertSame('10.0.0.5', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
        $this->assertSame('http', $captured->getUri()->getScheme());
    }

    public function testInvalidPortIsIgnored(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.5']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-Port' => '99999'],
        );

        $this->assertNull($captured->getUri()->getPort());
    }

    public function testWhenAllHopsTrustedFallsBackToLeftmost(): void
    {
        $middleware = (new TrustedProxyMiddleware())->withTrustedProxies(['10.0.0.0/8']);

        $captured = $this->runWith(
            $middleware,
            ['REMOTE_ADDR' => '10.0.0.5'],
            ['X-Forwarded-For' => '10.0.0.1, 10.0.0.2, 10.0.0.3'],
        );

        $this->assertSame('10.0.0.1', $captured->getAttribute(TrustedProxyMiddleware::ATTRIBUTE_CLIENT_IP));
    }

    /**
     * @param array<string,string> $serverParams
     * @param array<string,string> $headers
     */
    private function runWith(
        TrustedProxyMiddleware $middleware,
        array $serverParams,
        array $headers,
    ): ServerRequestInterface {
        $app = AppFactory::create();
        $app->add($middleware);
        $app->addRoutingMiddleware();

        $captured = null;
        $app->get('/test', function ($request, $response) use (&$captured) {
            $captured = $request;

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', 'http://internal/test', $serverParams);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $app->handle($request);

        $this->assertNotNull($captured);

        return $captured;
    }
}
