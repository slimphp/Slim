<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;

class JsonPhpErrorMessage implements RenderableInterface
{

    public function render($args)
    {
        $error = $args[0];
        $displayErrorDetails = $args[1];

        $json = [
            'message' => 'Slim Application Error',
        ];

        if ($displayErrorDetails) {
            $json['error'] = [];

            do {
                $json['error'][] = [
                    'type' => get_class($error),
                    'code' => $error->getCode(),
                    'message' => $error->getMessage(),
                    'file' => $error->getFile(),
                    'line' => $error->getLine(),
                    'trace' => explode("\n", $error->getTraceAsString()),
                ];
            } while ($error = $error->getPrevious());
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }
}