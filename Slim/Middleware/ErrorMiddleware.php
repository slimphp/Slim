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
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\ErrorHandlerInterface;
use Exception;
use Throwable;

class ErrorMiddleware
{
    /**
     * @var bool
     */
    protected $displayErrorDetails;
    /**
     * @var array
     */
    protected $handlers = [];
    /**
     * @var ErrorHandlerInterface|callable
     */
    protected $defaultErrorHandler;
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * ErrorMiddleware constructor.
     * @param bool $displayErrorDetails
     * @param ErrorHandlerInterface|callable $defaultErrorHandler
     */
    public function __construct($displayErrorDetails, $defaultErrorHandler = null)
    {
        $this->displayErrorDetails = $displayErrorDetails;
        $this->defaultErrorHandler = is_null($defaultErrorHandler) ? new ErrorHandler() : $defaultErrorHandler;
    }

    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->request = $request;
        $this->response = $response;

        try {
            return $next($this->request, $this->response);
        } catch (Exception $e) {
            return $this->handleException($e);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Resolve custom error handler from container or use default ErrorHandler
     * @param Exception|Throwable $exception
     * @return ResponseInterface
     */
    public function handleException($exception)
    {
        /**
         * Retrieve request object from exception and replace current request object if not null
         */
        if (method_exists($exception, 'getRequest')) {
            $request = $exception->getRequest();
            if ($request !== null) {
                $this->request = $request;
            }
        }

        $exceptionType = get_class($exception);
        $handler = $this->getErrorHandler($exceptionType);
        $params = [$this->request, $this->response, $exception, $this->displayErrorDetails];

        return call_user_func_array($handler, $params);
    }

    /**
     * Get callable to handle scenarios where an error
     * occurs when processing the current request.
     *
     * @param string $type
     * @return callable|ErrorHandler
     *
     * @throws \RuntimeException
     */
    public function getErrorHandler($type)
    {
        if (isset($this->handlers[$type]) && is_callable($this->handlers[$type])) {
            return $this->handlers[$type];
        }
        return $this->defaultErrorHandler;
    }

    /**
     * Set callable to handle scenarios where an error
     * occurs when processing the current request.
     *
     * This service MUST return a callable that accepts
     * three arguments optionally four arguments.
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Exception
     * 4. Boolean displayErrorDetails (optional)
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param string $type
     * @param callable|ErrorHandlerInterface $handler
     *
     * @throws \RuntimeException
     */
    public function setErrorHandler($type, $handler)
    {
        $this->handlers[$type] = $handler;
    }

    /**
     * Set callable as the default Slim application error handler.
     *
     * This service MUST return a callable that accepts
     * three arguments optionally four arguments.
     *
     * 1. Instance of \Psr\Http\Message\ServerRequestInterface
     * 2. Instance of \Psr\Http\Message\ResponseInterface
     * 3. Instance of \Exception
     * 4. Boolean displayErrorDetails (optional)
     *
     * The callable MUST return an instance of
     * \Psr\Http\Message\ResponseInterface.
     *
     * @param callable|ErrorHandler $handler
     *
     * @throws \RuntimeException
     */
    public function setDefaultErrorHandler($handler)
    {
        $this->defaultErrorHandler = $handler;
    }
}
