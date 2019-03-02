<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class MockContainerNotFoundException
 * @package Slim\Tests\Mocks
 */
class MockContainerNotFoundException extends Exception implements NotFoundExceptionInterface
{
}
