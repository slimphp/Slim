<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

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
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * @param array $methods
     * @return self
     */
    public function setAllowedMethods(array $methods): HttpMethodNotAllowedException
    {
        $this->allowedMethods = $methods;
        return $this;
    }
}
