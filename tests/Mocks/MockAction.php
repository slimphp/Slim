<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use InvalidArgumentException;

/**
 * Mock object for Slim\Tests\AppTest
 */
class MockAction
{
    public function __call($name, array $arguments)
    {
        if (count($arguments) !== 3) {
            throw new InvalidArgumentException("Not a Slim call");
        }

        $arguments[1]->write(json_encode(compact('name') + ['arguments' => $arguments[2]]));

        return $arguments[1];
    }
}
