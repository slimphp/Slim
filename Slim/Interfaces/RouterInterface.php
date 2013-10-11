<?php

namespace Slim\Interfaces;

interface RouterInterface
{
    public function getCurrentRoute();

    public function getMatchedRoutes($httpMethod, $resourceUri, $reload = false);

    public function map(RouteInterface $route);

    public function pushGroup($group, $middleware = array());

    public function popGroup();

    public function getNamedRoute($name);

    public function hasNamedRoute($name);
}
