<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Error\Renderers;

use Slim\Error\AbstractErrorRenderer;

/**
 * Default Slim application Plain Text Error Renderer
 */
class PlainTextErrorRenderer extends AbstractErrorRenderer
{
    /**
     * @return string
     */
    public function render()
    {
        $e = $this->exception;

        $text = 'Slim Application Error:' . PHP_EOL;
        $text .= $this->formatExceptionFragment($e);

        while ($e = $e->getPrevious()) {
            $text .= PHP_EOL . 'Previous Error:' . PHP_EOL;
            $text .= $this->formatExceptionFragment($e);
        }

        return $text;
    }

    /**
     * @param \Exception|\Throwable $exception
     * @return string
     */
    private function formatExceptionFragment($exception)
    {
        $text = sprintf('Type: %s' . PHP_EOL, get_class($exception));

        $code = $exception->getCode();
        if ($code !== null) {
            $text .= sprintf('Code: %s' . PHP_EOL, $code);
        }

        $message = $exception->getMessage();
        if ($message !== null) {
            $text .= sprintf('Message: %s' . PHP_EOL, htmlentities($message));
        }

        $file = $exception->getFile();
        if ($file !== null) {
            $text .= sprintf('File: %s' . PHP_EOL, $file);
        }

        $line = $exception->getLine();
        if ($line !== null) {
            $text .= sprintf('Line: %s' . PHP_EOL, $line);
        }

        $trace = $exception->getTraceAsString();
        if ($trace !== null) {
            $text .= sprintf('Trace: %s', $trace);
        }

        return $text;
    }
}
