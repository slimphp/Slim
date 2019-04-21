<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class OutputBufferingMiddleware implements MiddlewareInterface
{
    public const APPEND = 'append';
    public const PREPEND = 'prepend';

    /**
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * @var string
     */
    protected $style;

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param string                 $style Either "append" or "prepend"
     */
    public function __construct(StreamFactoryInterface $streamFactory, string $style = 'append')
    {
        $this->streamFactory = $streamFactory;
        $this->style = $style;

        if (!in_array($style, [static::APPEND, static::PREPEND], true)) {
            throw new InvalidArgumentException("Invalid style `{$style}`. Must be `append` or `prepend`");
        }
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            ob_start();
            /** @var ResponseInterface $response */
            $response = $handler->handle($request);
            $output = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        if (!empty($output) && $response->getBody()->isWritable()) {
            if ($this->style === static::PREPEND) {
                $body = $this->streamFactory->createStream();
                $body->write($output . $response->getBody());
                $response = $response->withBody($body);
            } elseif ($this->style === static::APPEND) {
                $response->getBody()->write($output);
            }
        }

        return $response;
    }
}
