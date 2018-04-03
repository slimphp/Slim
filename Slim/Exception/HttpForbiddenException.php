<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Exception;

class HttpForbiddenException extends HttpSpecializedException
{
    protected $code = 403;
    protected $message = 'Forbidden.';
    protected $title = '403 Forbidden';
    protected $description = 'You are not permitted to perform the requested operation.';
}
