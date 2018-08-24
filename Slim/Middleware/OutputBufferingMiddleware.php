<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Body;
use Throwable;

class OutputBufferingMiddleware
{
    const APPEND = 'append';
    const PREPEND = 'prepend';

    /**
     * @var string
     */
    protected $style;

    /**
     * Constructor
     *
     * @param string $style Either "append" or "prepend"
     */
    public function __construct(string $style = 'append')
    {
        if (!in_array($style, [static::APPEND, static::PREPEND])) {
            throw new \InvalidArgumentException('Invalid style. Must be one of: append, prepend');
        }

        $this->style = $style;
    }

    /**
     * Invoke
     *
     * @param  ServerRequestInterface $request   PSR7 server request
     * @param  ResponseInterface      $response  PSR7 response
     * @param  callable               $next      Middleware callable
     * @return ResponseInterface                 PSR7 response
     * @throws Throwable
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        try {
            ob_start();
            $newResponse = $next($request, $response);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        if (!empty($output) && $newResponse->getBody()->isWritable()) {
            if ($this->style === static::PREPEND) {
                $body = new Body(fopen('php://temp', 'r+'));
                $body->write($output . $newResponse->getBody());
                $newResponse = $newResponse->withBody($body);
            } elseif ($this->style === static::APPEND) {
                $newResponse->getBody()->write($output);
            }
        }

        return $newResponse;
    }
}
