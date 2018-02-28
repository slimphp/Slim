<?php
namespace Slim\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Exception;
use Throwable;

/**
 * Class HttpSpecializedException
 * @package Slim\Exception
 */
abstract class HttpSpecializedException extends HttpException
{
    /**
     * HttpSpecializedException constructor.
     * @param ServerRequestInterface $request
     * @param string|null $message
     * @param Exception|Throwable|null $previous
     */
    public function __construct(ServerRequestInterface $request, $message = null, $previous = null)
    {
        parent::__construct($request, $message, $this->code, $previous);
    }
}
