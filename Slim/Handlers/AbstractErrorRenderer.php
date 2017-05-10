<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

use Exception;
use Slim\Exception\PhpException;
use Slim\Interfaces\ErrorRendererInterface;

/**
 * Default Slim application error renderer
 *
 * It outputs the error message and diagnostic information in either JSON, XML,
 * or HTML based on the Accept header.
 */
abstract class AbstractErrorRenderer implements ErrorRendererInterface
{
    /**
     * @var Exception
     */
    protected $exception;
    /**
     * @var bool
     */
    protected $displayErrorDetails;

    /**
     * AbstractErrorRenderer constructor.
     * @param Exception $exception
     * @param bool $displayErrorDetails
     */
    public function __construct(Exception $exception, $displayErrorDetails = false)
    {
        $this->exception = $exception;
        $this->displayErrorDetails = $displayErrorDetails;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->exception instanceof PhpException) {
            return $this->renderLanguageExceptionOutput();
        }

        return $this->renderGenericExceptionOutput();
    }
}
