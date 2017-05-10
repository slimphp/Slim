<?php
namespace Slim\Exception;

use Exception;

class PhpException extends Exception
{
    protected $exception;

    /**
     * PhpException constructor.
     * @param $exception
     */
    public function __construct($exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return mixed
     */
    public function getException()
    {
        return $this->exception;
    }
}