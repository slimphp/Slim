<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

use Exception;
use Slim\Interfaces\ErrorHandlerInterface;
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;

/**
 * Default Slim application error handler
 *
 * It outputs the error message and diagnostic information in either JSON, XML,
 * or HTML based on the Accept header.
 */
abstract class AbstractHandler implements ErrorHandlerInterface
{
    /**
     * Known handled content types
     *
     * @var array
     */
    protected $knownContentTypes = [
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
    ];
    /**
     * @var bool
     */
    protected $displayErrorDetails = false;
    /**
     * @var string
     */
    protected $contentType = 'text/plain';
    /**
     * @var string
     */
    protected $method;
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var Exception
     */
    protected $exception;
    /**
     * @var HTMLErrorRenderer|JSONErrorRenderer|XMLErrorRenderer|PlainTextErrorRenderer
     */
    protected $renderer;
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * AbstractHandler constructor.
     * @param bool $displayErrorDetails
     */
    public function __construct($displayErrorDetails = true)
    {
        $this->displayErrorDetails = $displayErrorDetails;
    }

    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param Exception             $exception The caught Exception object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, Exception $exception)
    {
        $this->request = $request;
        $this->response = $response;
        $this->exception = $exception;
        $this->method = $request->getMethod();
        $this->contentType = $this->resolveContentType();
        $this->renderer = $this->resolveRenderer();
        $this->statusCode = $this->resolveStatusCode();

        return $this->respond();
    }

    /**
     * Determine which content type we know about is wanted using Accept header
     *
     * Note: This method is a bare-bones implementation designed specifically for
     * Slim's error handling requirements. Consider a fully-feature solution such
     * as willdurand/negotiation for any other situation.
     *
     * @return string
     */
    protected function resolveContentType()
    {
        $acceptHeader = $this->request->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(explode(',', $acceptHeader), $this->knownContentTypes);

        if (count($selectedContentTypes)) {
            return current($selectedContentTypes);
        }

        // handle +json and +xml specially
        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (in_array($mediaType, $this->knownContentTypes)) {
                return $mediaType;
            }
        }

        return 'text/html';
    }

    /**
     * @return HTMLErrorRenderer|JSONErrorRenderer|XMLErrorRenderer|PlainTextErrorRenderer
     * @throws HttpBadRequestException
     */
    protected function resolveRenderer()
    {
        if ($this->method === 'OPTIONS') {
            return PlainTextErrorRenderer::class;
        }

        switch ($this->contentType) {
            case 'application/json':
                return JSONErrorRenderer::class;

            case 'text/xml':
            case 'application/xml':
                return XMLErrorRenderer::class;

            case 'text/html':
                return HTMLErrorRenderer::class;

            default:
                throw new UnexpectedValueException(sprintf('Cannot render unknown content type: %s', $this->contentType));
        }
    }

    /**
     * @return int
     */
    protected function resolveStatusCode()
    {
        $statusCode = 500;

        if ($this->exception instanceof HttpException)
        {
            $statusCode = $this->exception->getCode();
        }

        return $statusCode;
    }
}
