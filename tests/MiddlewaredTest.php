<?php

class MiddlewaredStackTest {
    use \Slim\Middlewared;

    public function __invoke()
    {
        echo 'Hello';
    }
}

class MiddlewaredTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->app = new MiddlewaredStackTest();
    }


    public function testExecMiddlewares()
    {
        $this->expectOutputString('FooHello');
        $this->app->add(function($req, $resp, $next){
            echo 'Foo';
            $next();
        });
        $this->app->run(null, null);
    }
}
