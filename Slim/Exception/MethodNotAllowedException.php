<?php

namespace Slim\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MethodNotAllowedException extends SlimException
{
    /**
     * Create new exception
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $allowedMethods
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response, array $allowedMethods)
    {
        parent::__construct($request, $response);
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
}
