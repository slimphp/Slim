<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This class wraps a PSR-15 style single pass middleware,
 * into an invokable double pass middleware.
 *
 * This is an internal class. This class is an implementation detail
 * and is used only inside of the Slim  application; it is not visible
 * to—and should not be used by—end users.
 *
 * @link https://github.com/php-fig/http-server-middleware/blob/master/src/MiddlewareInterface.php
 * @link https://github.com/php-fig/http-server-handler/blob/master/src/RequestHandlerInterface.php
 */
final class PsrMiddleware implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface
     */
    private $middleware;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var callable
     */
    private $next;

    public function __construct(MiddlewareInterface $middleware)
    {
        $this->middleware = $middleware;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        $this->response = $response;
        $this->next = $next;

        /* Call the PSR-15 middleware and let it return to our handle()
         * method by passing `$this` as RequestHandler. */
        return $this->middleware->process($request, $this);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->next)($request, $this->response);
    }
}
