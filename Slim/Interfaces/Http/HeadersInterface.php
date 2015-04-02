<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Interfaces\Http;

use \Slim\Interfaces\CollectionInterface;

/**
 * Headers Interface
 *
 * @package Slim
 * @author  John Porter
 * @since   3.0.0
 */
interface HeadersInterface extends CollectionInterface
{
    public function normalizeKey($key);
}
