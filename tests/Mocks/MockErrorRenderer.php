<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use Slim\Handlers\AbstractErrorRenderer;

/**
 * Mock object for Slim\Tests\AppTest
 */
class MockErrorRenderer extends AbstractErrorRenderer
{
    public function renderPhpExceptionOutput()
    {
        return '';
    }

    public function renderGenericExceptionOutput()
    {
        return '';
    }
}
