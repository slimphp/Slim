<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Error\Renderers;

use Slim\Error\AbstractErrorRenderer;
use Throwable;

use function get_class;
use function htmlentities;
use function sprintf;

/**
 * Default Slim application HTML Error Renderer
 */
class HtmlErrorRenderer extends AbstractErrorRenderer
{
    /**
     * @param Throwable $exception
     * @param bool      $displayErrorDetails
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        if ($displayErrorDetails) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->renderExceptionFragment($exception);
        } else {
            $html = "<p>{$this->getErrorDescription($exception)}</p>";
        }

        return $this->renderHtmlBody($this->getErrorTitle($exception), $html);
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    private function renderExceptionFragment(Throwable $exception): string
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        $code = $exception->getCode();
        if ($code !== null) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        $message = $exception->getMessage();
        if ($message !== null) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }

        $file = $exception->getFile();
        if ($file !== null) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        $line = $exception->getLine();
        if ($line !== null) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        $trace = $exception->getTraceAsString();
        if ($trace !== null) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }

        return $html;
    }

    /**
     * @param string $title
     * @param string $html
     * @return string
     */
    public function renderHtmlBody(string $title = '', string $html = ''): string
    {
        return sprintf(
            '<html>' .
            '   <head>' .
            "       <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            '       <title>%s</title>' .
            '       <style>' .
            '           body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif}' .
            '           h1{margin:0;font-size:48px;font-weight:normal;line-height:48px}' .
            '           strong{display:inline-block;width:65px}' .
            '       </style>' .
            '   </head>' .
            '   <body>' .
            '       <h1>%s</h1>' .
            '       <div>%s</div>' .
            '       <a href="#" onClick="window.history.go(-1)">Go Back</a>' .
            '   </body>' .
            '</html>',
            $title,
            $title,
            $html
        );
    }
}
