<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Exception;

use RuntimeException;
use Interop\Container\Exception\NotFoundException as InteropNotFoundException;

/**
 * Not Found Exception
 */
class NotFoundException extends RuntimeException implements InteropNotFoundException
{

}
