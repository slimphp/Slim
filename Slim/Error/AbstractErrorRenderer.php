<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Error;

use Slim\Http\Body;
use Slim\Interfaces\ErrorRendererInterface;

/**
 * Abstract Slim application error renderer
 *
 * It outputs the error message and diagnostic information in one of the following formats:
 * JSON, XML, Plain Text or HTML
 */
abstract class AbstractErrorRenderer implements ErrorRendererInterface
{
    /**
     * @var \Exception
     */
    protected $exception;
    /**
     * @var bool
     */
    protected $displayErrorDetails;

    /**
     * AbstractErrorRenderer constructor.
     * @param \Exception|\Throwable $exception
     * @param bool $displayErrorDetails
     */
    public function __construct($exception, $displayErrorDetails)
    {
        $this->exception = $exception;
        $this->displayErrorDetails = $displayErrorDetails;
    }

    /**
     * @return Body
     */
    public function renderWithBody()
    {
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($this->render());
        return $body;
    }
}
