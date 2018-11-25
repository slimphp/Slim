<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
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

        $response = $arguments[1];
        $contents = json_encode(compact('name') + ['arguments' => $arguments[2]]);
        $arguments[1]->getBody()->write($contents);

        return $response;
    }
}
