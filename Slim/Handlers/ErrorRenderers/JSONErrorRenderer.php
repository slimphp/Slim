<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

/**
 * Default Slim application JSON Error Renderer
 */
class JSONErrorRenderer extends AbstractErrorRenderer
{
    public function renderThrowableOutput()
    {
        $e = $this->exception;
        $error = ['message' => 'Slim Application Error'];

        if ($this->displayErrorDetails) {
            $error['exception'] = [];

            do {
                $error['exception'][] = [
                    'type' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ];
            } while ($e = $e->getPrevious());
        }

        return json_encode($error, JSON_PRETTY_PRINT);
    }

    public function renderGenericOutput()
    {
        $error = ['message' => $this->exception->getMessage()];

        return json_encode($error, JSON_PRETTY_PRINT);
    }
}