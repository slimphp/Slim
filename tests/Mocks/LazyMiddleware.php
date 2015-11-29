<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Mock object for Slim\Tests\AppTest
 */
class LazyMiddleware
{
    private $name;

    public function __construct($name = 'default')
    {
        $this->name = $name;
        echo 'Construct'.$this->name;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $requestInterface
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $requestInterface,
        ResponseInterface $response,
        callable $next
    ) {
        $response->getBody()->write('In'.$this->name);

        $next($requestInterface, $response);

        $response->getBody()->write('Out'.$this->name);

        return $response;
    }
}