<?php
namespace Slim\Exception;

class HttpMethodNotAllowedException extends HttpSpecializedException
{
    /**
     * @var array
     */
    protected $allowedMethods = [];

    protected $code = 405;
    protected $message = 'Method not allowed.';
    protected $title = '405 Method Not Allowed';
    protected $description = 'The request method is not supported for the requested resource.';

    /**
     * @return string
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }

    /**
     * @param array $methods
     */
    public function setAllowedMethods(array $methods)
    {
        $this->allowedMethods = $methods;
    }
}
