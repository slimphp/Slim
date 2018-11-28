<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;


class PlainNotFoundOutput implements RenderableInterface
{

    public function render($args)
    {
        return 'Not found';
    }
}