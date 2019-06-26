<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Error;

use Exception;
use ReflectionClass;
use RuntimeException;
use Slim\Error\Renderers\HtmlErrorRenderer;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Error\Renderers\PlainTextErrorRenderer;
use Slim\Error\Renderers\XmlErrorRenderer;
use Slim\Tests\TestCase;
use stdClass;

class AbstractErrorRendererTest extends TestCase
{
    public function testHTMLErrorRendererDisplaysErrorDetails()
    {
        $exception = new RuntimeException('Oops..');
        $renderer = new HtmlErrorRenderer();
        $output = $renderer->__invoke($exception, true);

        $this->assertRegExp('/.*The application could not run because of the following error:.*/', $output);
    }

    public function testHTMLErrorRendererNoErrorDetails()
    {
        $exception = new RuntimeException('Oops..');
        $renderer = new HtmlErrorRenderer();
        $output = $renderer->__invoke($exception, false);

        $this->assertRegExp('/.*A website error has occurred. Sorry for the temporary inconvenience.*/', $output);
    }

    public function testHTMLErrorRendererRenderFragmentMethod()
    {
        $exception = new Exception('Oops..', 500);
        $renderer = new HtmlErrorRenderer();
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
        $renderer = new JsonErrorRenderer();
        $reflectionRenderer = new ReflectionClass(JsonErrorRenderer::class);

        $method = $reflectionRenderer->getMethod('formatExceptionFragment');
        $method->setAccessible(true);

        $fragment = $method->invoke($renderer, $exception);
        $output = json_encode(json_decode($renderer->__invoke($exception, true)));
        $expectedString = json_encode(['message' => 'Oops..', 'exception' => [$fragment]]);

        $this->assertEquals($output, $expectedString);
    }

    public function testJSONErrorRendererDoesNotDisplayErrorDetails()
    {
        $exception = new Exception('Oops..');

        $renderer = new JsonErrorRenderer();
        $output = json_encode(json_decode($renderer->__invoke($exception, false)));

        $this->assertEquals($output, json_encode(['message' => 'Oops..']));
    }

    public function testJSONErrorRendererDisplaysPreviousError()
    {
        $previousException = new Exception('Oh no!');
        $exception = new Exception('Oops..', 0, $previousException);

        $renderer = new JsonErrorRenderer();
        $reflectionRenderer = new ReflectionClass(JsonErrorRenderer::class);
        $method = $reflectionRenderer->getMethod('formatExceptionFragment');
        $method->setAccessible(true);

        $output = json_encode(json_decode($renderer->__invoke($exception, true)));

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

        $renderer = new XmlErrorRenderer();

        /** @var stdClass $output */
        $output = simplexml_load_string($renderer->__invoke($exception, true));

        $this->assertEquals($output->message[0], 'Ooops...');
        $this->assertEquals((string) $output->exception[0]->type, 'Exception');
        $this->assertEquals((string) $output->exception[1]->type, 'RuntimeException');
    }

    public function testPlainTextErrorRendererFormatFragmentMethod()
    {
        $exception = new Exception('Oops..', 500);
        $renderer = new PlainTextErrorRenderer();
        $reflectionRenderer = new ReflectionClass(PlainTextErrorRenderer::class);

        $method = $reflectionRenderer->getMethod('formatExceptionFragment');
        $method->setAccessible(true);
        $output = $method->invoke($renderer, $exception);

        $this->assertRegExp('/.*Type:*/', $output);
        $this->assertRegExp('/.*Code:*/', $output);
        $this->assertRegExp('/.*Message*/', $output);
        $this->assertRegExp('/.*File*/', $output);
        $this->assertRegExp('/.*Line*/', $output);
    }

    public function testPlainTextErrorRendererDisplaysErrorDetails()
    {
        $previousException = new RuntimeException('Oops..');
        $exception = new Exception('Ooops...', 0, $previousException);

        $renderer = new PlainTextErrorRenderer();
        $output = $renderer->__invoke($exception, true);

        $this->assertRegExp('/Ooops.../', $output);
    }
}
