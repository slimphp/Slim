<?php
namespace Slim\Exception;

class HttpUnauthorizedException extends HttpException
{
    protected $code = 401;
    protected $message = 'Unauthorized.';
    protected $title = '401 Unauthorized';
    protected $description = 'The request requires valid user authentication.';
}
