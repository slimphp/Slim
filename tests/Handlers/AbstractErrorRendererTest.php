<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Slim\Handlers\ErrorRenderers\PlainTextErrorRenderer;
use Exception;

class AbstractErrorRendererTest extends TestCase
{
    public function testPlainTextErrorRenderDoesNotDisplayErrorDetails()
    {
        $exception = new Exception('Oops..');
        $renderer = new PlainTextErrorRenderer($exception, true);

        $this->assertEquals('Oops..', $renderer->render());
    }
}
