<?php
namespace Slim;

class RouteIterator implements \Iterator 
{
    private $routes = array();
    private $httpMethod;
    private $resourceUri;


    public function push(\Slim\Route $val)
    {
        $this->routes[] = $val;
    }
    public function reset(){
        return reset($this->routes);
    }
    public function getAll(){
        return $this->routes;
    }

    public function setFilter($httpMethod, $resourceUri) 
    {
        $this->httpMethod = $httpMethod;
        $this->resourceUri = $resourceUri;
    }

    function rewind() 
    {
        return reset($this->routes);
    }

    function current()
    {
        return current($this->routes);
    }

    function key()
    {
        return key($this->routes);
    }

    function next()
    {
        return next($this->routes);
    }
    function valid() 
    {
        $route = $this->current();
        if (key($this->routes) !== null && (!$route->supportsHttpMethod($this->httpMethod) || !$route->matches($this->resourceUri))){
            $this->next();
            $this->valid();
        }
        return key($this->routes) !== null;
    }
}