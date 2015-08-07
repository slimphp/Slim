<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Handlers\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\Strategies\NameBased;
use Slim\Http\Request;
use Slim\Tests\Mocks\MockAction;

class NameBasedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NameBased
     */
    protected $strategy;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;


    protected function setUp()
    {
        $this->strategy = new NameBased();
        $this->request = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $this->response = $this->getMock('Slim\Http\Response');
    }

    /**
     * @covers Slim\Handlers\Strategies\NameBased::__invoke
     */
    public function testWithSingleParameter()
    {
        $callable = function ($id) {
            return 'PHPUnit ' . $id;
        };

        $return = $this->strategy->__invoke($callable, $this->request, $this->response, ['id' => 4]);
        $this->assertEquals('PHPUnit 4', $return);
    }

    /**
     * @covers Slim\Handlers\Strategies\NameBased::__invoke
     */
    public function testWithMultipleParameters()
    {
        $callable = function ($name, $color, $animal) {
            return $name . ' likes ' . $color . ' ' . $animal . 's';
        };

        $routeArguments = [
            'name' => 'Michel',
            'color' => 'grizzly',
            'animal' => 'bear',
        ];

        $return = $this->strategy->__invoke($callable, $this->request, $this->response, $routeArguments);
        $this->assertEquals('Michel likes grizzly bears', $return);
    }

    /**
     * @covers Slim\Handlers\Strategies\NameBased::__invoke
     */
    public function testWithMultipleParametersDifferentOrder()
    {
        $callable = function ($fruit, $nr, $name) {
            return $name . ' ate ' . $nr . ' ' . $fruit . 's';
        };

        $routeArguments = [
            'name' => 'Josh',
            'fruit' => 'banana',
            'nr' => 2,
        ];

        $return = $this->strategy->__invoke($callable, $this->request, $this->response, $routeArguments);
        $this->assertEquals('Josh ate 2 bananas', $return);
    }

    /**
     * @covers Slim\Handlers\Strategies\NameBased::__invoke
     */
    public function testWithRequest()
    {
        $callable = function ($id, Request $request) {
            $this->assertEquals('9', $id);
            $this->assertInstanceOf('Slim\Http\Request', $request);
        };

        $this->strategy->__invoke($callable, $this->request, $this->response, ['id' => '9']);
    }

    /**
     * @covers Slim\Handlers\Strategies\NameBased::__invoke
     */
    public function testWithResponse()
    {
        $callable = function ($response) {
            $this->assertInstanceOf('Slim\Http\Response', $response);
        };

        $this->strategy->__invoke($callable, $this->request, $this->response, []);
    }

    /**
     * @covers Slim\Handlers\Strategies\NameBased::__invoke
     */
    public function testWithOptionalParameters()
    {
        $callable = function ($action, $name = 'Rob', $amount = 5) {
            return $name . ' ' . $action . 's ' . $amount . ' times';
        };

        $routeArguments = [
            'action' => 'jump',
            'amount' => 2
        ];

        $return = $this->strategy->__invoke($callable, $this->request, $this->response, $routeArguments);
        $this->assertEquals('Rob jumps 2 times', $return);
    }

    /**
     * @covers Slim\Handlers\Strategies\NameBased::__invoke
     */
    public function testWithMissingParameter()
    {
        $callable = function ($id, $name, $amount) {
            $this->fail('Callable should not be called');
        };

        $routeArguments = [
            'id' => 5,
            'amount' => 22,
        ];

        $this->setExpectedException(
            'RuntimeException',
            'Could not find a value for parameter "name" (available arguments: id, amount, req, request, res, response).'
        );
        $this->strategy->__invoke($callable, $this->request, $this->response, $routeArguments);
    }

    /**
     * @covers Slim\Handlers\Strategies\NameBased::__invoke
     */
    public function testWithClassMethod()
    {
        $callable = [new MockAction(), '__call'];

        $routeArguments = [
            'name' => 'Michel',
            'arguments' => ['', $this->response, ''],
        ];

        $return = $this->strategy->__invoke($callable, $this->request, $this->response, $routeArguments);
        $this->assertEquals($this->response, $return);
    }
}
