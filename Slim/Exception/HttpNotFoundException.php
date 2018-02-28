<?php
namespace Slim\Exception;

class HttpNotFoundException extends HttpSpecializedException
{
    protected $code = 404;
    protected $message = 'Not found.';
    protected $title = '404 Not Found';
    protected $description = 'The requested resource could not be found. Please verify the URI and try again.';
}