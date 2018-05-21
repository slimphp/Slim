<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Interfaces;

/**
 * Renderer Interface
 *
 * @package Slim
 */
interface RendererInterface
{
    /**
     * Render the template $templateName using $data
     *
     * @param string $templateName
     * @param array $data
     *
     * @return string
     */
    public function render($templateName, array $data = []);
}