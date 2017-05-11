<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers\ErrorRenderers;

use Error;
use Exception;
use RuntimeException;
use Slim\Handlers\AbstractErrorRenderer;

/**
 * Default Slim application HTML Error Renderer
 */
class HtmlErrorRenderer extends AbstractErrorRenderer
{
    public function renderPhpExceptionOutput()
    {
        $e = $this->exception;
        $title = 'Slim Application Error';

        if ($this->displayErrorDetails) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->renderExceptionFragment($e);
        } else {
            $html = '<p>A website error has occurred. Sorry for the temporary inconvenience.</p>';
        }

        $output = sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            "<title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{" .
            "display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $html
        );

        return $output;
    }

    public function renderGenericExceptionOutput()
    {
        $e = $this->exception;
        $title = '';
        $description = '';

        if ($this->displayErrorDetails) {
            $description = $e->getMessage();
        }

        if (method_exists($e, 'getTitle')) {
            $title = $e->getTitle();
        }

        if (method_exists($e, 'getDescription')) {
            $description = $e->getDescription();
        }

        $output = sprintf(
            "<html><head><title>%s</title><style>" .
            "body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}" .
            "h1{margin:0;font-size:48px;font-weight:normal;line-height:48px}" .
            "strong{display:inline-block;width:65px}" .
            "</style></head><body><h1>%s</h1><p>%s</p>" .
            "<a href='#' onClick='window.history.go(-1)'>Go Back</a></body></html>",
            $title,
            $title,
            $description
        );

        return $output;
    }

    /**
     * @param Exception $exception
     * @return string
     */
    public function renderExceptionFragment($exception)
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        if (($code = $exception->getCode())) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        if (($message = $exception->getMessage())) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }

        if (($file = $exception->getFile())) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        if (($line = $exception->getLine())) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        if (($trace = $exception->getTraceAsString())) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }

        return $html;
    }
}
