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

/**
 * Default Slim application Plain Text Error Renderer
 */
class PlainTextErrorRenderer extends AbstractErrorRenderer
{
    /**
     * @param Throwable $exception
     * @param bool      $displayErrorDetails
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $text = "Slim Application Error:\n";
        $text .= $this->formatExceptionFragment($exception);

        while ($displayErrorDetails && $exception = $exception->getPrevious()) {
            $text .= "\nPrevious Error:\n";
            $text .= $this->formatExceptionFragment($exception);
        }

        return $text;
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    private function formatExceptionFragment(Throwable $exception): string
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
            $text .= sprintf('Trace: %s', $trace);
        }

        return $text;
    }
}
