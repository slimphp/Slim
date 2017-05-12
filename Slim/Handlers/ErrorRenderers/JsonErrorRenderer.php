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
 * Default Slim application JSON Error Renderer
 */
class JsonErrorRenderer extends AbstractErrorRenderer
{
    public function renderPhpExceptionOutput()
    {
        $message = 'Slim Application Error';
        return $this->formatExceptionPayload($message);
    }

    public function renderGenericExceptionOutput()
    {
        $message = $this->exception->getMessage();
        return $this->formatExceptionPayload($message);
    }

    /**
     * @param $message
     * @return string
     */
    public function formatExceptionPayload($message)
    {
        $e = $this->exception;
        $error = ['message' => $message];

        if ($this->displayErrorDetails) {
            $error['exception'] = [];
            do {
                $error['exception'][] = $this->formatExceptionFragment($e);
            } while ($e = $e->getPrevious());
        }

        return json_encode($error, JSON_PRETTY_PRINT);
    }

    /**
     * @param \Exception|\Throwable $e
     * @return array
     */
    public function formatExceptionFragment($e)
    {
        return [
            'type' => get_class($e),
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }
}
