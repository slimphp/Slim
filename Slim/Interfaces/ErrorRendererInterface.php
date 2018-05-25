<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Slim\Http\Body;
use Throwable;

/**
 * ErrorRendererInterface
 *
 * @package Slim
 * @since   4.0.0
 */
interface ErrorRendererInterface
{
    /**
     * @param Throwable $exception
     * @param bool $displayErrorDetails
     * @return string
     */
    public function render(Throwable $exception, bool $displayErrorDetails): string;

    /**
     * @param Throwable $exception
     * @param bool $displayErrorDetails
     * @return Body
     */
    public function renderWithBody(Throwable $exception, bool $displayErrorDetails): Body;
}
