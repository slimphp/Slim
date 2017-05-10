<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotAllowedException;
use Slim\Exception\PhpException;
use Slim\Http\Body;

/**
 * Default Slim application error handler
 *
 * It outputs the error message and diagnostic information in either JSON, XML,
 * or HTML based on the Accept header.
 */
class ErrorHandler extends AbstractErrorHandler
{
}
