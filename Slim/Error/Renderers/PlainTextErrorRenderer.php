<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Error\Renderers;

use Slim\Error\AbstractErrorRenderer;

/**
 * Default Slim application Plain Text Error Renderer
 */
class PlainTextErrorRenderer extends AbstractErrorRenderer
{
    /**
     * @param \Exception|\Throwable $exception
     * @param bool $displayErrorDetails
     * @return string
     */
    public function render($exception, $displayErrorDetails)
    {
        $text = "Slim Application Error:\n";
        $text .= $this->formatExceptionFragment($exception);

        do {
            $text .= "\nPrevious Error:\n";
            $text .= $this->formatExceptionFragment($exception);
        } while ($exception = $exception->getPrevious());

        return $text;
    }

    /**
     * @param \Exception|\Throwable $exception
     * @return string
     */
    private function formatExceptionFragment($exception)
    {
        $text = sprintf("Type: %s\n", get_class($exception));

        $code = $exception->getCode();
        if ($code !== null) {
            $text .= sprintf("Code: %s\n", $code);
        }

        $message = $exception->getMessage();
        if ($message !== null) {
            $text .= sprintf("Message: %s\n", htmlentities($message));
        }

        $file = $exception->getFile();
        if ($file !== null) {
            $text .= sprintf("File: %s\n", $file);
        }

        $line = $exception->getLine();
        if ($line !== null) {
            $text .= sprintf("Line: %s\n", $line);
        }

        $trace = $exception->getTraceAsString();
        if ($trace !== null) {
            $text .= sprintf("Trace: %s", $trace);
        }

        return $text;
    }
}
