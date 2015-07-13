<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Exception;

use Exception as BaseException;
use Psr\Http\Message\ResponseInterface;

/**
 * Stop Exception
 *
 * This Exception is thrown when the Slim application needs to abort
 * processing and return control flow to the outer PHP script.
 */
class Exception extends BaseException
{
    /**
     * A response object to send to the HTTP client
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Create new exception
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        parent::__construct();
        $this->response = $response;
    }

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
