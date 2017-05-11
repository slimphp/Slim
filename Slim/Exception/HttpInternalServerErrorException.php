<?php
namespace Slim\Exception;

class HttpInternalServerErrorException extends HttpException
{
    protected $code = 500;
    protected $message = 'Internal server error.';
    protected $title = '500 Internal Server Error';
    protected $description = 'Unexpected condition encountered preventing server from fulfilling request.';
}
