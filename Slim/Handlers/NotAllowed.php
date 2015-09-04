<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Handlers;

use Psr\Http\Message\ServerRequestInterface;
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
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     * @param  string[]               $methods  Allowed HTTP methods
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $methods)
    {
        $allow = implode(', ', $methods);
        $body = new Body(fopen('php://temp', 'r+'));

        if ($request->getMethod() === 'OPTIONS') {
            $status = 200;
            $contentType = 'text/plain';
            $body->write('Allowed methods: ' . $allow);
        } else {
            $status = 405;
            $contentType = 'text/html';
            $body->write('<p>Method not allowed. Must be one of: ' . $allow . '</p>');
        }

        return $response
                ->withStatus($status)
                ->withHeader('Content-type', $contentType)
                ->withHeader('Allow', $allow)
                ->withBody($body);
    }
}
