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
 * ErrorRendererInterface
 *
 * @package Slim
 * @since   4.0.0
 */
interface ErrorRendererInterface
{
    /**
     * @param \Exception|\Throwable $exception
     * @param bool $displayErrorDetails
     * @return string
     */
    public function render($exception, $displayErrorDetails);

    /**
     * @param \Exception|\Throwable $exception
     * @param bool $displayErrorDetails
     * @return \Slim\Http\Body
     */
    public function renderWithBody($exception, $displayErrorDetails);
}
