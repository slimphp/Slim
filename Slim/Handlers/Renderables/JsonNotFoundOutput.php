<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;


class JsonNotFoundOutput implements RenderableInterface
{
    public function render($args)
    {
        return '{"message":"Not found"}';
    }
}