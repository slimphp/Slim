<?php
namespace Slim\Exception;

class HttpBadRequestException extends HttpException
{
    protected $code = 400;
    protected $message = 'Bad request.';
}