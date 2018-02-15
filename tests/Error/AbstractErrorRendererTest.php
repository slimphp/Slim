<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Error;

use PHPUnit\Framework\TestCase;
use Slim\Error\Renderers\HtmlErrorRenderer;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Error\Renderers\XmlErrorRenderer;
use Exception;
use ReflectionClass;
use RuntimeException;

class AbstractErrorRendererTest extends TestCase
{
    public function testHTMLErrorRendererDisplaysErrorDetails()
    {
        $exception = new RuntimeException('Oops..');
        $renderer = new HtmlErrorRenderer($exception, true);
        $output = $renderer->render();

        $this->assertRegExp('/.*The application could not run because of the following error:.*/', $output);
    }

    public function testHTMLErrorRendererRenderFragmentMethod()
    {
        $exception = new Exception('Oops..', 500);
        $renderer = new HtmlErrorRenderer($exception, true);
        $reflectionRenderer = new ReflectionClass(HtmlErrorRenderer::class);
        $method = $reflectionRenderer->getMethod('renderExceptionFragment');
        $method->setAccessible(true);
        $output = $method->invoke($renderer, $exception);

        $this->assertRegExp('/.*Type:*/', $output);
        $this->assertRegExp('/.*Code:*/', $output);
        $this->assertRegExp('/.*Message*/', $output);
        $this->assertRegExp('/.*File*/', $output);
        $this->assertRegExp('/.*Line*/', $output);
    }

    public function testJSONErrorRendererDisplaysErrorDetails()
    {
        $exception = new Exception('Oops..');
        $renderer = new JsonErrorRenderer($exception, true);
        $reflectionRenderer = new ReflectionClass(JsonErrorRenderer::class);
        $method = $reflectionRenderer->getMethod('formatExceptionFragment');
        $method->setAccessible(true);
        $fragment = $method->invoke($renderer, $exception);
        $output = json_encode(json_decode($renderer->render()));
        $expectedString = json_encode(['message' => 'Oops..', 'exception' => [$fragment]]);

        $this->assertEquals($output, $expectedString);
    }

    public function testJSONErrorRendererDoesNotDisplayErrorDetails()
    {
        $exception = new Exception('Oops..');
        $renderer = new JsonErrorRenderer($exception, false);
        $output = json_encode(json_decode($renderer->render()));
        $this->assertEquals($output, json_encode(['message' => 'Oops..']));
    }

    public function testJSONErrorRendererDisplaysPreviousError()
    {
        $previousException = new Exception('Oh no!');
        $exception = new Exception('Oops..', 0, $previousException);
        $renderer = new JsonErrorRenderer($exception, true);
        $reflectionRenderer = new ReflectionClass(JsonErrorRenderer::class);
        $method = $reflectionRenderer->getMethod('formatExceptionFragment');
        $method->setAccessible(true);
        $output = json_encode(json_decode($renderer->render()));

        $fragments = [
            $method->invoke($renderer, $exception),
            $method->invoke($renderer, $previousException),
        ];

        $expectedString = json_encode(['message' => 'Oops..', 'exception' => $fragments]);

        $this->assertEquals($output, $expectedString);
    }

    public function testXMLErrorRendererDisplaysErrorDetails()
    {
        $previousException = new RuntimeException('Oops..');
        $exception = new Exception('Ooops...', 0, $previousException);
        $renderer = new XmlErrorRenderer($exception, true);
        $output = simplexml_load_string($renderer->render());

        $this->assertEquals($output->message[0], 'Ooops...');
        $this->assertEquals((string)$output->exception[0]->type, 'Exception');
        $this->assertEquals((string)$output->exception[1]->type, 'RuntimeException');
    }
}
