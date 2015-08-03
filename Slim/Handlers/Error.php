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
 * Default error handler
 *
 * This is the default Slim application error handler. All it does is output
 * a clean and simple HTML page with diagnostic information.
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
        $title = 'Slim Application Error';
        $html = '<p>The application could not run because of the following error:</p>';
        $html .= '<h2>Details</h2>';
        $html .= $this->renderException($exception);

        while ($exception = $exception->getPrevious()) {
            $html .= '<h2>Previous exception</h2>';
            $html .= $this->renderException($exception);
        }

        $output = sprintf(
            "<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;" .
            "width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $html
        );

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response
                ->withStatus(500)
                ->withHeader('Content-type', 'text/html')
                ->withBody($body);
    }

    /**
     * Render exception as html.
     *
     * @param Exception $exception
     *
     * @return string
     */
    private function renderException(Exception $exception)
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
}
