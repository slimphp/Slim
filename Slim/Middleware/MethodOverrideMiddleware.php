<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Override HTTP Request method by given body param or custom header
 */
class MethodOverrideMiddleware
{
    /**
     * Invoke
     *
     * @param  ServerRequestInterface $request   PSR7 server request
     * @param  ResponseInterface      $response  PSR7 response
     * @param  callable               $next      Middleware callable
     * @return ResponseInterface                 PSR7 response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $method = $this->getOverrideMethod($request);

        return $next($request->withMethod($method), $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getOverrideMethod(ServerRequestInterface $request)
    {
        $method = $request->getHeaderLine('X-Http-Method-Override');

        if (!$method && strtoupper($request->getMethod()) == 'POST') {
            $body = $request->getParsedBody();

            if (!empty($body['_METHOD'])) {
                $method = $body['_METHOD'];
            }

            if ($request->getBody()->eof()) {
                $request->getBody()->rewind();
            }
        }

        if (!$method) {
            $method = $request->getMethod();
        }

        return $method;
    }
}
