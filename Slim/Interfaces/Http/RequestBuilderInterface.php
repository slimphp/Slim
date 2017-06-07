<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Interfaces\Http;
use Slim\Collection;

/**
 * Request Builder Interface
 *
 * @package Slim
 * @since   3.0.0
 */
interface RequestBuilderInterface
{
    public static function build(Collection $settings);
}
