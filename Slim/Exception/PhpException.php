<?php
namespace Slim\Exception;

use Exception;

class PhpException extends Exception
{
    /**
     * PhpException constructor.
     * @param $exception
     */
    public function __construct($exception)
    {
        parent::__construct('PHP Error', 500, $exception);
    }
}
