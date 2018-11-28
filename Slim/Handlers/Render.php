<?php
namespace Slim\Handlers;

use Slim\Interfaces\RenderableInterface;


class Render
{

    public static function make($args)
    {
        $args = func_get_args($args);
        $renderable = $args[0];
        unset($args[0]);

        $class = __NAMESPACE__ . '\Renderables\\' . $renderable;

        if (class_exists($class)) {
            $obj = new $class;
            if ($obj instanceof RenderableInterface) {
                return $obj->render(array_values($args));
            } else {
                throw new \Exception(
                    "class {$class} must implement \Slim\Interfaces\RenderableInterface interface"
                );
            }
        } else {
            throw new \Exception(
                "class {$class} not found"
            );
        }
    }
}