<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Exception;

class HttpInternalServerErrorException extends HttpSpecializedException
{
    protected $code = 500;
    protected $message = 'Internal server error.';
    protected $title = '500 Internal Server Error';
    protected $description = 'Unexpected condition encountered preventing server from fulfilling request.';
}
