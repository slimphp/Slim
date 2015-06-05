<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Interfaces\Http;

/**
 * Environment Interface
 *
 * @package Slim
 * @since   3.0.0
 */
interface EnvironmentInterface
{
    public static function mock(array $settings = []);
}
