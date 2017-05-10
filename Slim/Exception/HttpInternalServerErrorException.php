<?php
namespace Slim\Exception;

class HttpInternalServerErrorException extends HttpException
{
    protected $code = 500;
    protected $message = 'Internal server error.';
}