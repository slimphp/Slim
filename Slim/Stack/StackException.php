<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Stack;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
* StackException
*
* This exception is used internally by the middleware stack runner, to capture the
* outer request and response instances at the time of a middleware exception,
* together with the exception itself. A StackException will bubble up if there
* are linked stacks.
*/
class StackException extends Exception
{
    /**
     * The original exception
     *
     * @var Exception
     */
    protected $exception;

    /**
     * The outer request object at the time of the exception
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * The outer response object at the time of the exception
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Create new StackException
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Exception $source The original exception
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response, Exception $exception)
    {
        parent::__construct();
        $this->request = $request;
        $this->response = $response;
        $this->exception = $exception;
    }

    /**
    * Get the original exception
    *
    * @return Exception
    */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Get outer request object at the time of the exception
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get outer response object at the time of the exception
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
