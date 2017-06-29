<?php
namespace Slim\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Body;

class ContentLength
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
        $response = $next($request, $response);

        // Add Content-Length header if not already added
        $size = $response->getBody()->getSize();
        if ($size !== null && !$response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', (string) $size);
        }

        return $response;
    }
}
