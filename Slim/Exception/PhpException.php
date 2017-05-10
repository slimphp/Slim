<?php
namespace Slim\Exception;

use Exception;

class PhpException extends Exception
{
    protected $exception;

    /**
     * PhpException constructor.
     * @param Exception $exception
     */
    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}