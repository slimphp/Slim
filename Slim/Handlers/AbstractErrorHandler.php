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
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpNotAllowedException;
use Slim\Handlers\ErrorRenderers\PlainTextErrorRenderer;
use Slim\Handlers\ErrorRenderers\HTMLErrorRenderer;
use Slim\Handlers\ErrorRenderers\XMLErrorRenderer;
use Slim\Handlers\ErrorRenderers\JSONErrorRenderer;
use Slim\Http\Body;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\ErrorRendererInterface;

/**
 * Default Slim application error handler
 *
 * It outputs the error message and diagnostic information in either JSON, XML,
 * or HTML based on the Accept header.
 */
abstract class AbstractErrorHandler implements ErrorHandlerInterface
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
     * @var ErrorRendererInterface
     */
    protected $renderer = null;
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

        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (in_array($mediaType, $this->knownContentTypes)) {
                return $mediaType;
            }
        }

        return 'text/html';
    }

    /**
     * Determine which renderer to use based on content type
     * Overloaded $renderer from calling class takes precedence over all
     *
     * @return ErrorRendererInterface
     * @throws UnexpectedValueException
     */
    protected function resolveRenderer()
    {
        $renderer = null;

        if (!is_null($this->renderer)) {
            $renderer = $this->renderer;
        } else if ($this->method === 'OPTIONS') {
            $this->statusCode = 200;
            $this->contentType = 'text/plain';
            $renderer = PlainTextErrorRenderer::class;
        } else {
            switch ($this->contentType) {
                case 'application/json':
                    $renderer = JSONErrorRenderer::class;
                break;

                case 'text/xml':
                case 'application/xml':
                    $renderer = XMLErrorRenderer::class;
                break;

                case 'text/html':
                    $renderer = HTMLErrorRenderer::class;
                break;

                default:
                    throw new UnexpectedValueException(sprintf('Cannot render unknown content type: %s', $this->contentType));
                break;
            }
        }

        return new $renderer($this->exception, $this->displayErrorDetails);
    }

    /**
     * @return int
     */
    protected function resolveStatusCode()
    {
        $statusCode = 500;

        if ($this->exception instanceof HttpException) {
            $statusCode = $this->exception->getCode();
        }

        return $statusCode;
    }

    /**
     * @return ResponseInterface
     */
    public function respond()
    {
        $e = $this->exception;
        $response = $this->response;
        $output = $this->renderer->render();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        if ($e instanceof HttpNotAllowedException) {
            $response = $response->withHeader('Allow', $e->getAllowedMethods());
        }

        return $response
            ->withStatus($this->statusCode)
            ->withHeader('Content-type', $this->contentType)
            ->withBody($body);
    }
}
