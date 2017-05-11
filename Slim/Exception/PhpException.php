<?php
namespace Slim\Exception;

use Exception;
use Throwable;

class PhpException extends Exception
{
    /**
     * PhpException constructor.
     * @param Exception|Throwable $exception
     */
    public function __construct($exception)
    {
        parent::__construct('PHP Error', 500, $exception);
    }
}
