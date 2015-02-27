<?php


class MiddlewaredStackTest {
    use \Slim\Middlewared;

    public function __invoke()
    {
        echo 'Hello';
    }

    public function run()
    {
        $this->assumeIsBooted();
    }
}

class MiddlewaredTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->app = new MiddlewaredStackTest();
    }

    public function testWithoutNudeApp()
    {
        $this->expectOutputString('Hello');
        $this->app->execMiddlewareStack(
            $this->getMock('Psr\Http\Message\RequestInterface'),
            $this->getMock('Psr\Http\Message\ResponseInterface')
        );
    }

    public function testExecMiddlewares()
    {
        $this->expectOutputString('FooHello');
        $this->app->add(function($req, $resp, $next){
            echo 'Foo';
            $next();
            return $resp;
        });
        $this->app->execMiddlewareStack(
            $this->getMock('Psr\Http\Message\RequestInterface'),
            $this->getMock('Psr\Http\Message\ResponseInterface')
        );
    }

    public function testStackOrder()
    {
        $mw1 = function() {};
        $mw2 = function() {};
        $mw3 = function() {};
        $this->app->addMiddlewares([$mw1, $mw2, $mw3]);
        $first_layer = $this->app->getTopLevelMiddleware();
        $this->assertEquals($mw3, $first_layer->getCallable());
        $second_layer = $first_layer->getNext();
        $this->assertEquals($mw2, $second_layer->getCallable());
        $third_layer = $second_layer->getNext();
        $this->assertEquals($mw3, $third_layer->getCallable());
        $app = $third_layer->getNext();
        $this->assertEquals($this->app, $app);
    }

    public function testAddMiddlewareWillStackIsRunningThrowException()
    {
        $this->app->addMiddleware(function($req, $resp) {
            $this->app->add(function($req, $resp){
                return $resp;
            });
            return $resp;
        });
        $this->setExpectedException('RuntimeException');
        $this->app->execMiddlewareStack(
            $this->getMock('Psr\Http\Message\RequestInterface'),
            $this->getMock('Psr\Http\Message\ResponseInterface')
        );
    }
}
