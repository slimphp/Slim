<?php
/*
 * This file is part of the slim package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slim\Handlers\Strategies;


use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class RequestResponseArgs implements InvocationStrategyInterface
{

    /**
     * @param ContainerInterface     $container
     * @param array                  $route
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return mixed
     */
    public function invoke(ContainerInterface $container, array $route, ServerRequestInterface $request, ResponseInterface $response)
    {
        $arguments = [$request, $response];
        foreach ($route[2] as $k => $v) {
            $decoded = urldecode($v);
            $request = $request->withAttribute($k, $decoded);
            array_push($arguments, $decoded);
        }
        return call_user_func_array($route[1], $arguments);
    }
}
