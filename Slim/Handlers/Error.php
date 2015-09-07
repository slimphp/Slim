<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Handlers;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Body;

/**
 * Default Slim application error handler
 *
 * It outputs the error message and diagnostic information in either JSON, XML,
 * or HTML based on the Accept header.
 */
class Error
{
    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param Exception              $exception The caught Exception object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, Exception $exception)
    {
        $contentType = $this->determineContentType($request->getHeaderLine('Accept'));
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJsonErrorMessage($exception);
                break;

            case 'application/xml':
                $output = $this->renderXmlErrorMessage($exception);
                break;

            case 'text/html':
            default:
                $contentType = 'text/html';
                $output = $this->renderHtmlErrorMessage($exception);
                break;
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response
                ->withStatus(500)
                ->withHeader('Content-type', $contentType)
                ->withBody($body);
    }

    /**
     * Render HTML error page
     *
     * @param  Exception $exception
     * @return string
     */
    private function renderHtmlErrorMessage(Exception $exception)
    {
        $title = 'Slim Application Error';
        $html = '<p>The application could not run because of the following error:</p>';
        $html .= '<h2>Details</h2>';
        $html .= $this->renderHtmlException($exception);

        while ($exception = $exception->getPrevious()) {
            $html .= '<h2>Previous exception</h2>';
            $html .= $this->renderHtmlException($exception);
        }

        $output = sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            "<title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;" .
            "width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $html
        );

        return $output;
    }

    /**
     * Render exception as HTML.
     *
     * @param Exception $exception
     *
     * @return string
     */
    private function renderHtmlException(Exception $exception)
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = str_replace(['#', '\n'], ['<div>#', '</div>'], $exception->getTraceAsString());

        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));
        if ($code) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }
        if ($message) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', $message);
        }
        if ($file) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }
        if ($line) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }
        if ($trace) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', $trace);
        }
        return $html;
    }

    /**
     * Render JSON error
     *
     * @param  Exception $exception
     * @return string
     */
    private function renderJsonErrorMessage(Exception $exception)
    {
        $error = ['message' => 'Slim Application Error'];
        $error['exception'][] = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode("\n", $exception->getTraceAsString()),
        ];

        while ($exception = $exception->getPrevious()) {
            $error['exception'][] = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return json_encode($error);
    }

    /**
     * Render XML error
     *
     * @param  Exception $exception
     * @return string
     */
    private function renderXmlErrorMessage(Exception $exception)
    {
        $xml = "<root>\n  <message>Slim Application Error</message>\n";

        $xml .= <<<EOT
  <exception>
    <code>{$exception->getCode()}</code>
    <message>{$exception->getMessage()}</message>
    <file>{$exception->getFile()}</file>
    <line>{$exception->getLine()}</line>
    <trace>{$exception->getTraceAsString()}</trace>
  </exception>

EOT;

        while ($exception = $exception->getPrevious()) {
            $xml .= <<<EOT
  <exception>
    <code>{$exception->getCode()}</code>
    <message>{$exception->getMessage()}</message>
    <file>{$exception->getFile()}</file>
    <line>{$exception->getLine()}</line>
    <trace>{$exception->getTraceAsString()}</trace>
  </exception>

EOT;
        }
        $xml .="</root>";
        return $xml;
    }

    /**
     * Read the accept header and determine which content type we know about
     * is wanted.
     *
     * @param  string $acceptHeader Accept header from request
     * @return string
     */
    private function determineContentType($acceptHeader)
    {
        $list = explode(',', $acceptHeader);
        $known = ['application/json', 'application/xml', 'text/html'];
        
        foreach ($list as $type) {
            if (in_array($type, $known)) {
                return $type;
            }
        }

        return 'text/html';
    }
}
