<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;


class XmlNotFoundOutput implements RenderableInterface
{

    public function render($args)
    {
        return '<root><message>Not found</message></root>';
    }
}