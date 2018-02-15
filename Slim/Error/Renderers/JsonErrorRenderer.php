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
 * Default Slim application JSON Error Renderer
 */
class JsonErrorRenderer extends AbstractErrorRenderer
{
    /**
     * @return string
     */
    public function render()
    {
        $e = $this->exception;
        $error = ['message' => $e->getMessage()];

        if ($this->displayErrorDetails) {
            $error['exception'] = [];
            do {
                $error['exception'][] = $this->formatExceptionFragment($e);
            } while ($e = $e->getPrevious());
        }

        return json_encode($error, JSON_PRETTY_PRINT);
    }

    /**
     * @param \Exception|\Throwable $exception
     * @return array
     */
    private function formatExceptionFragment($exception)
    {
        return [
            'type' => get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }
}
