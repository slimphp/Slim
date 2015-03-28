<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Handlers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Body;

/**
 * Default not allowed handler
 *
 * This is the default Slim application error handler. All it does is output
 * a clean and simple HTML page with diagnostic information.
 */
class NotAllowed
{
    /**
     * Invoke error handler
     *
     * @param  RequestInterface  $request   The most recent Request object
     * @param  ResponseInterface $response  The most recent Response object
     * @param  string[]          $methods   Allowed HTTP methods
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, array $methods)
    {
        return $response
                ->withStatus(405)
                ->withHeader('Content-type', 'text/html')
                ->withHeader('Allow', implode(', ', $methods))
                ->withBody(new Body(fopen('php://temp', 'r+')))
                ->write('<p>Method not allowed. Must be one of: ' . implode(', ', $methods) . '</p>');
    }
}
