<?php
namespace Slim\Exception;

class HttpBadGatewayException extends HttpException
{
    protected $code = 502;
    protected $message = 'Bad Gateway.';
    protected $title = 'Bad Gateway';
    protected $description = 'Invalid response from an upstream server, unable to fulfil the request.';
}
