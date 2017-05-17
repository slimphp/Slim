<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

/**
 * Default Slim application Plain Text Error Renderer
 */
class PlainTextErrorRenderer extends AbstractErrorRenderer
{
    public function renderThrowableOutput()
    {
        return $this->exception->getMessage();
    }

    public function renderGenericOutput()
    {
        return $this->exception->getMessage();
    }
}
