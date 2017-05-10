<?php
namespace Slim\Exception;

class HttpNotFoundException extends HttpException
{
    protected $code = 404;
    protected $message = 'Not found.';
    protected $title = '404 Not Found';
    protected $description = 'The requested resource could not be found but may be available in the future. Subsequent requests by the client are permissible.';
}
