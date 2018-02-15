<?php
namespace Slim\Exception;

class HttpNotImplementedException extends HttpException
{
    protected $code = 501;
    protected $message = 'Not implemented.';
    protected $title = '501 Not Implemented';
    protected $description = 'The server does not support the functionality required to fulfill the request.';
}
