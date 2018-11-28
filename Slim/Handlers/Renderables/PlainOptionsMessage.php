<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;

class PlainOptionsMessage implements RenderableInterface
{

    public function render($args)
    {
        return $this->renderPlainOptionsMessage($args[0]);
    }



    /**
     * Render PLAIN message for OPTIONS response
     *
     * @param  array                  $methods
     * @return string
     */
    protected function renderPlainOptionsMessage($methods)
    {
        $allow = implode(', ', $methods);

        return 'Allowed methods: ' . $allow;
    }
}
