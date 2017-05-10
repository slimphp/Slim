<?php
namespace Slim\Exception;

class HttpInternalServerErrorException extends HttpException
{
    protected $code = 500;
    protected $message = 'Internal server error.';
    protected $title = '500 Internal Server Error';
    protected $description = 'A generic error message, given when an unexpected condition was encountered and no more specific message is suitable.';
}