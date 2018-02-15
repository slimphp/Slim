<?php
namespace Slim\Exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class HttpException extends \Exception
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
    protected $details;
    /**
     * @var string|null
     */
    protected $title = '';
    /**
     * @var string
     */
    protected $description = '';

    /**
     * HttpException constructor.
     * @param string|array|null $details
     */
    public function __construct($details = null)
    {
        if (is_string($details)) {
            parent::__construct($details);
        } else {
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
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
