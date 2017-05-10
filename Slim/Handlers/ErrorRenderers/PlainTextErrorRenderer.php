<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers\ErrorRenderers;

use Slim\Handlers\AbstractErrorRenderer;

/**
 * Default Slim application Plain Text Error Renderer
 */
class PlainTextErrorRenderer extends AbstractErrorRenderer
{
    public function renderPhpExceptionOutput()
    {
        return $this->exception->getMessage();
    }

    public function renderGenericExceptionOutput()
    {
        return $this->exception->getMessage();
    }
}