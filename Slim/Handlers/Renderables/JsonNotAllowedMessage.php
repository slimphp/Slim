<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;

class JsonNotAllowedMessage implements RenderableInterface
{


    public function render($args)
    {
        $methods = $args[0];
        return $this->renderJsonNotAllowedMessage($methods);
    }


    /**
     * Render JSON not allowed message
     *
     * @param  array                  $methods
     * @return string
     */
    protected function renderJsonNotAllowedMessage($methods)
    {
        $allow = implode(', ', $methods);

        return '{"message":"Method not allowed. Must be one of: ' . $allow . '"}';
    }


}