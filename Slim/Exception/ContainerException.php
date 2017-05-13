<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE (MIT License)
 */
namespace Slim\Exception;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;

/**
 * Container Exception
 */
class ContainerException extends InvalidArgumentException implements ContainerExceptionInterface
{

}
