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

class RequestResponse implements InvocationStrategyInterface
{

    /**
     * @param ContainerInterface     $container
     * @param array                  $route
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return mixed
     */
    public function invoke(
        ContainerInterface $container,
        array $route,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        foreach ($route[2] as $k => $v) {
            $request = $request->withAttribute($k, urldecode($v));
        }
        return $route[1]($request, $response, []);
    }
}
