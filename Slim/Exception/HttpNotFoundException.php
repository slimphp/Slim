<?php
namespace Slim\Exception;

class HttpNotFoundException extends HttpException
{
    protected $code = 404;
    protected $message = 'The page you requested cannot be found.';
}