<?php
namespace Slim\Exception;

class HttpUnauthorizedException extends HttpException
{
    protected $code = 401;
    protected $message = 'Unauthorized.';
    protected $title = '401 Unauthorized';
    protected $description = 'The request has not been applied because it lacks valid authentication credentials for the target resource.';
}