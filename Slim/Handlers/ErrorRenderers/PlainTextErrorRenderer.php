<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers\ErrorRenderers;

use Slim\Handlers\AbstractErrorRenderer;

/**
 * Default Slim application Plain Text Error Renderer
 */
class PlainTextErrorRenderer extends AbstractErrorRenderer
{
    public function renderPhpExceptionOutput()
    {
        $e = $this->exception;
        return $this->renderExceptionFragment($e);
    }

    public function renderGenericExceptionOutput()
    {
        $e = $this->exception;

        if (!$this->displayErrorDetails) {
            return $this->renderExceptionFragment($e);
        }

        return $e->getMessage();
    }

    public function renderExceptionFragment()
    {
        $e = $this->exception;
        $text = sprintf('Type: %s' . PHP_EOL, get_class($e));

        if ($code = $e->getCode()) {
            $text .= sprintf('Code: %s' . PHP_EOL, $code);
        }
        if ($message = $e->getMessage()) {
            $text .= sprintf('Message: %s' . PHP_EOL, htmlentities($message));
        }
        if ($file = $e->getFile()) {
            $text .= sprintf('File: %s' . PHP_EOL, $file);
        }
        if ($line = $e->getLine()) {
            $text .= sprintf('Line: %s' . PHP_EOL, $line);
        }
        if ($trace = $e->getTraceAsString()) {
            $text .= sprintf('Trace: %s', $trace);
        }

        return $text;
    }
}