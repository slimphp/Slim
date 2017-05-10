<?php
namespace Slim\Exception;

class HttpNotImplementedException extends HttpException
{
    protected $code = 501;
    protected $message = 'Not implemented.';
}