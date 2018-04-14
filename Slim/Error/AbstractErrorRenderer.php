<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Error;

use Slim\Http\Body;
use Slim\Interfaces\ErrorRendererInterface;
use Exception;
use Throwable;

/**
 * Abstract Slim application error renderer
 *
 * It outputs the error message and diagnostic information in one of the following formats:
 * JSON, XML, Plain Text or HTML
 */
abstract class AbstractErrorRenderer implements ErrorRendererInterface
{
    /**
     * @param Exception|Throwable $exception
     * @param bool $displayErrorDetails
     * @return Body
     */
    public function renderWithBody($exception, $displayErrorDetails)
    {
        $output = $this->render($exception, $displayErrorDetails);
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);
        return $body;
    }
}
