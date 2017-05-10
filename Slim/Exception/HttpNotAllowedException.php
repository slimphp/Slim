<?php
namespace Slim\Exception;

class HttpNotAllowedException extends HttpException
{
    protected $code = 405;
    protected $message = 'Method not allowed.';

    /**
     * @return string
     */
    public function getAllowedMethods()
    {
        $details = $this->getDetails();

        if (isset($details['allowedMethods'])) {
            $allowedMethods = $details['allowedMethods'];

            if (is_array($allowedMethods)) {
                return implode(',', $allowedMethods);
            } else if (is_string($allowedMethods)) {
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
        if (is_null($this->details)) {
            $this->details = [];
        }

        $this->details['allowedMethods'] = $methods;
    }
}