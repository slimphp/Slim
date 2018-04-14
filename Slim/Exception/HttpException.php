<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Exception;
use Throwable;

class HttpException extends Exception
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

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
     * @param ServerRequestInterface $request
     * @param string|null $message
     * @param int $code
     * @param Exception|Throwable|null $previous
     */
    public function __construct(ServerRequestInterface $request, $message = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
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
