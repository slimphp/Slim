<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Exception;

class HttpUnprocessableEntityException extends HttpSpecializedException
{
    protected $code = 422;
    protected $message = 'Unprocessable Entity.';
    protected $title = '422 Unprocessable Entity';
    protected $description = 'The server does not able to process the contained instructions.';
}
