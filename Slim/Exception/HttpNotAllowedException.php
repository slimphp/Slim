<?php
namespace Slim\Exception;

class HttpNotAllowedException extends HttpException
{
    protected $code = 405;
    protected $message = 'Method not allowed.';
    protected $title = '405 Method Not Allowed';
    protected $description = 'The request method is not supported for the requested resource.';

    /**
     * @return string
     */
    public function getAllowedMethods()
    {
        $details = $this->getDetails();

        if (isset($details['allowedMethods'])) {
            $allowedMethods = $details['allowedMethods'];

            if (is_array($allowedMethods)) {
                return implode(', ', $allowedMethods);
            }

            if (is_string($allowedMethods)) {
                return $allowedMethods;
            }

            return '';
        }
    }

    /**
     * @param string|array $methods
     */
    public function setAllowedMethods($methods)
    {
        if ($this->details === null) {
            $this->details = [];
        }

        $this->details['allowedMethods'] = $methods;
    }
}
