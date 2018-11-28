<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;

class XmlNotAllowedMessage implements RenderableInterface
{


    public function render($args)
    {
        $methods = $args[0];
        return $this->renderXmlNotAllowedMessage($methods);
    }


        /**
     * Render XML not allowed message
     *
     * @param  array                  $methods
     * @return string
     */
    protected function renderXmlNotAllowedMessage($methods)
    {
        $allow = implode(', ', $methods);

        return "<root><message>Method not allowed. Must be one of: $allow</message></root>";
    }


}