<?php

namespace Slim\Exception;

use Psr\Http\Message\ResponseInterface;

class MethodNotAllowedException extends SlimException
{
    /**
     * Create new exception
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response, array $allowedMethods)
    {
        parent::__construct($response);
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
