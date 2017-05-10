<?php
namespace Slim\Exception;

class HttpNotImplementedException extends HttpException
{
    protected $code = 501;
    protected $message = 'Not implemented.';
    protected $title = '501 Not Implemented';
    protected $description = 'The server either does not recognize the request method, or it lacks the ability to fulfil the request.';
}