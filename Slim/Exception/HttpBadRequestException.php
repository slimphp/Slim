<?php
namespace Slim\Exception;

class HttpBadRequestException extends HttpException
{
    protected $code = 400;
    protected $message = 'Bad request.';
    protected $title = '400 Bad Request';
    protected $description = 'The server cannot or will not process the request due to an apparent client error.';
}