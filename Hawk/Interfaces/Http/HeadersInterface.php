<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Hawk\Interfaces\Http;

use Hawk\Interfaces\CollectionInterface;

/**
 * Headers Interface
 *
 * @package Slim
 * @since   3.0.0
 */
interface HeadersInterface extends CollectionInterface
{
    public function add($key, $value);

    public function normalizeKey($key);
}
