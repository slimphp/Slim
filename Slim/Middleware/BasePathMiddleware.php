<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouterInterface;

final class BasePathMiddleware implements MiddlewareInterface
{
    private RouterInterface $router;

    private string $phpSapi;

    /**
     * The constructor.
     *
     * @param RouterInterface $router The router
     * @param string $phpSapi The type of interface between web server and PHP
     *
     * Supported: 'apache2handler'
     * Not supported: 'cgi', 'cgi-fcgi', 'fpm-fcgi', 'litespeed', 'cli-server'
     */
    public function __construct(RouterInterface $router, string $phpSapi = PHP_SAPI)
    {
        $this->phpSapi = $phpSapi;
        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $basePath = '';

        if ($this->phpSapi === 'apache2handler') {
            $basePath = $this->getBasePathByRequestUri($request);
        }

        // $request = $request->withAttribute(RouteMatch::BASE_PATH_ATTRIBUTE, $basePath);

        $this->router->setBasePath($basePath);

        return $handler->handle($request);
    }

    /**
     * Return basePath for most common webservers, such as Apache.
     * @param ServerRequestInterface $request
     */
    private function getBasePathByRequestUri(ServerRequestInterface $request): string
    {
        $basePath = $request->getUri()->getPath();
        $scriptName = $request->getServerParams()['SCRIPT_NAME'] ?? '';
        $scriptName = str_replace('\\', '/', dirname($scriptName, 2));

        if ($scriptName === '/') {
            return '';
        }

        $length = strlen($scriptName);
        $basePath = $length > 0 ? substr($basePath, 0, $length) : $basePath;

        return strlen($basePath) > 1 ? $basePath : '';
    }
}
