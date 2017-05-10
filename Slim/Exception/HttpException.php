<?php
namespace Slim\Exception;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class HttpException extends Exception
{
    /**
     * @var ServerRequestInterface|null
     */
    protected $request = null;
    /**
     * @var ResponseInterface|null
     */
    protected $response = null;
    /**
     * @var array|null
     */
    protected $details = null;
    /**
     * @var bool
     */
    protected $recoverable = true;

    /**
     * HttpException constructor.
     * @param string|array|null $details
     */
    public function __construct($details = null)
    {
        if (is_string($details)) {
            parent::__construct($details);
        } else if (!is_null($details)) {
            $this->details = $details;
        }
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @param array $details
     */
    public function setDetails(array $details)
    {
        $this->details = $details;
    }

    /**
     * @param bool $recoverable
     */
    public function setRecoverable($recoverable)
    {
        $this->recoverable = $recoverable;
    }

    /**
     * @return null|ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return null|ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return array|null|string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return bool
     */
    public function isRecoverable()
    {
        return $this->recoverable;
    }
}