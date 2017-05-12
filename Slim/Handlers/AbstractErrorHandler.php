<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpNotAllowedException;
use Slim\Handlers\ErrorRenderers\PlainTextErrorRenderer;
use Slim\Handlers\ErrorRenderers\HtmlErrorRenderer;
use Slim\Handlers\ErrorRenderers\XmlErrorRenderer;
use Slim\Handlers\ErrorRenderers\JsonErrorRenderer;
use Slim\Http\Body;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Exception;

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
        'text/plain'
    ];
    /**
     * @var bool
     */
    protected $displayErrorDetails;
    /**
     * @var string
     */
    protected $contentType;
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
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param Exception    $exception The caught Exception object
     * @param bool $displayErrorDetails Whether or not to display the error details
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        Exception $exception,
        $displayErrorDetails = false
    ) {
        $this->displayErrorDetails = $displayErrorDetails;
        $this->request = $request;
        $this->response = $response;
        $this->exception = $exception;
        $this->method = $request->getMethod();
        $this->statusCode = $this->determineStatusCode();
        $this->contentType = $this->determineContentType($request);
        $this->renderer = $this->determineRenderer();

        if (!$this->displayErrorDetails) {
            $this->writeToErrorLog();
        }

        return $this->formatResponse();
    }

    /**
     * Determine which content type we know about is wanted using Accept header
     *
     * Note: This method is a bare-bones implementation designed specifically for
     * Slim's error handling requirements. Consider a fully-feature solution such
     * as willdurand/negotiation for any other situation.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function determineContentType(ServerRequestInterface $request)
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(explode(',', $acceptHeader), $this->knownContentTypes);
        $count = count($selectedContentTypes);

        if ($count) {
            $current = current($selectedContentTypes);

            /**
             * Ensure other supported content types take precedence over text/plain
             * when multiple content types are provided via Accept header.
             */
            if ($current === 'text/plain' && $count > 1) {
                return next($selectedContentTypes);
            }

            return $current;
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
     *
     * @throws \RuntimeException
     */
    protected function determineRenderer()
    {
        $renderer = null;

        if ($this->method === 'OPTIONS') {
            $this->statusCode = 200;
            $this->contentType = 'text/plain';
        }

        if (!is_null($this->renderer)) {
            $renderer = $this->renderer;
            if (!is_subclass_of($renderer, AbstractErrorRenderer::class)) {
                throw new \RuntimeException(sprintf(
                    'Non compliant error renderer provided (%s). ' .
                    'Renderer expected to be a subclass of AbstractErrorRenderer',
                    $renderer
                ));
            }
        } else {
            switch ($this->contentType) {
                case 'application/json':
                    $renderer = JsonErrorRenderer::class;
                    break;

                case 'text/xml':
                case 'application/xml':
                    $renderer = XmlErrorRenderer::class;
                    break;

                case 'text/plain':
                    $renderer = PlainTextErrorRenderer::class;
                    break;

                default:
                case 'text/html':
                    $renderer = HtmlErrorRenderer::class;
                    break;
            }
        }

        return new $renderer($this->exception, $this->displayErrorDetails);
    }

    /**
     * @return int
     */
    protected function determineStatusCode()
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
    protected function formatResponse()
    {
        $e = $this->exception;
        $response = $this->response;
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($this->renderer->render());

        if ($e instanceof HttpNotAllowedException) {
            $response = $response->withHeader('Allow', $e->getAllowedMethods());
        }

        return $response
            ->withStatus($this->statusCode)
            ->withHeader('Content-type', $this->contentType)
            ->withBody($body);
    }

    /**
     * Write to the error log if displayErrorDetails is false
     *
     * @return void
     */
    protected function writeToErrorLog()
    {
        $renderer = new PlainTextErrorRenderer($this->exception, true);
        $error = $renderer->render();
        $error .= PHP_EOL . 'View in rendered output by enabling the "displayErrorDetails" setting.' . PHP_EOL;
        $this->logError($error);
    }

    /**
     * Wraps the error_log function so that this can be easily tested
     *
     * @param string $error
     */
    protected function logError($error)
    {
        error_log($error);
    }
}
