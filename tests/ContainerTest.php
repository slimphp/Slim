<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Slim\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test `get()` returns existing item
     */
    public function testGet()
    {
        $c = new Container;
        $c['foo'] = 'bar';
        $this->assertEquals('bar', $c->get('foo'));
    }

    /**
     * Test `get()` throws error if item does not exist
     *
     * @expectedException \Slim\Exception\NotFoundException
     */
    public function testGetWithError()
    {
        $c = new Container;
        $c->get('foo');
    }
}
