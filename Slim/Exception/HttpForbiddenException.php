<?php
namespace Slim\Exception;

class HttpForbiddenExceptionException extends HttpException
{
    protected $code = 403;
    protected $message = 'Forbidden.';
    protected $title = '403 Forbidden';
    protected $description = 'You are not permitted to perform the requested operation.';
}
