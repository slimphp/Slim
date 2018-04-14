<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Exception;

class HttpNotImplementedException extends HttpSpecializedException
{
    protected $code = 501;
    protected $message = 'Not implemented.';
    protected $title = '501 Not Implemented';
    protected $description = 'The server does not support the functionality required to fulfill the request.';
}
