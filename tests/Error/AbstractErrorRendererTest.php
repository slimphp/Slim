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
use Slim\Exception\HttpException;
use Slim\Tests\TestCase;
use stdClass;

use function json_decode;
use function json_encode;
use function simplexml_load_string;

class AbstractErrorRendererTest extends TestCase
{
    public function testHTMLErrorRendererDisplaysErrorDetails()
    {
        $exception = new RuntimeException('Oops..');
        $renderer = new HtmlErrorRenderer();
        $output = $renderer->__invoke($exception, true);

        $this->assertMatchesRegularExpression(
            '/.*The application could not run because of the following error:.*/',
            $output
        );
        $this->assertStringContainsString('Oops..', $output);
    }

    public function testHTMLErrorRendererNoErrorDetails()
    {
        $exception = new RuntimeException('Oops..');
        $renderer = new HtmlErrorRenderer();
        $output = $renderer->__invoke($exception, false);

        $this->assertMatchesRegularExpression(
            '/.*A website error has occurred. Sorry for the temporary inconvenience.*/',
            $output
        );
        $this->assertStringNotContainsString('Oops..', $output);
    }

    public function testHTMLErrorRendererRenderFragmentMethod()
    {
        $exception = new Exception('Oops..', 500);
        $renderer = new HtmlErrorRenderer();
        $reflectionRenderer = new ReflectionClass(HtmlErrorRenderer::class);

        $method = $reflectionRenderer->getMethod('renderExceptionFragment');
        $this->setAccessible($method);
        $output = $method->invoke($renderer, $exception);

        $this->assertMatchesRegularExpression('/.*Type:*/', $output);
        $this->assertMatchesRegularExpression('/.*Code:*/', $output);
        $this->assertMatchesRegularExpression('/.*Message*/', $output);
        $this->assertMatchesRegularExpression('/.*File*/', $output);
        $this->assertMatchesRegularExpression('/.*Line*/', $output);
    }

    public function testHTMLErrorRendererRenderHttpException()
    {
        $exceptionTitle = 'title';
        $exceptionDescription = 'description';

        $httpExceptionProphecy = $this->prophesize(HttpException::class);

        $httpExceptionProphecy
            ->getTitle()
            ->willReturn($exceptionTitle)
            ->shouldBeCalledOnce();

        $httpExceptionProphecy
            ->getDescription()
            ->willReturn($exceptionDescription)
            ->shouldBeCalledOnce();

        $renderer = new HtmlErrorRenderer();
        $output = $renderer->__invoke($httpExceptionProphecy->reveal(), false);

        $this->assertStringContainsString($exceptionTitle, $output, 'Should contain http exception title');
        $this->assertStringContainsString($exceptionDescription, $output, 'Should contain http exception description');
    }

    public function testJSONErrorRendererDisplaysErrorDetails()
    {
        $exception = new Exception('Oops..');
        $renderer = new JsonErrorRenderer();
        $reflectionRenderer = new ReflectionClass(JsonErrorRenderer::class);

        $method = $reflectionRenderer->getMethod('formatExceptionFragment');
        $this->setAccessible($method);

        $fragment = $method->invoke($renderer, $exception);
        $output = json_encode(json_decode($renderer->__invoke($exception, true)));
        $expectedString = json_encode(['message' => 'Slim Application Error', 'exception' => [$fragment]]);

        $this->assertSame($output, $expectedString);
    }

    public function testJSONErrorRendererDoesNotDisplayErrorDetails()
    {
        $exception = new Exception('Oops..');

        $renderer = new JsonErrorRenderer();
        $output = json_encode(json_decode($renderer->__invoke($exception, false)));

        $this->assertSame($output, json_encode(['message' => 'Slim Application Error']));
    }

    public function testJSONErrorRendererDisplaysPreviousError()
    {
        $previousException = new Exception('Oh no!');
        $exception = new Exception('Oops..', 0, $previousException);

        $renderer = new JsonErrorRenderer();
        $reflectionRenderer = new ReflectionClass(JsonErrorRenderer::class);
        $method = $reflectionRenderer->getMethod('formatExceptionFragment');
        $this->setAccessible($method);

        $output = json_encode(json_decode($renderer->__invoke($exception, true)));

        $fragments = [
            $method->invoke($renderer, $exception),
            $method->invoke($renderer, $previousException),
        ];

        $expectedString = json_encode(['message' => 'Slim Application Error', 'exception' => $fragments]);

        $this->assertSame($output, $expectedString);
    }

    public function testJSONErrorRendererRenderHttpException()
    {
        $exceptionTitle = 'title';

        $httpExceptionProphecy = $this->prophesize(HttpException::class);

        $httpExceptionProphecy
            ->getTitle()
            ->willReturn($exceptionTitle)
            ->shouldBeCalledOnce();

        $renderer = new JsonErrorRenderer();
        $output = json_encode(json_decode($renderer->__invoke($httpExceptionProphecy->reveal(), false)));

        $this->assertSame(
            $output,
            json_encode(['message' => $exceptionTitle]),
            'Should contain http exception title'
        );
    }


    public function testXMLErrorRendererDisplaysErrorDetails()
    {
        $previousException = new RuntimeException('Oops..');
        $exception = new Exception('Ooops...', 0, $previousException);

        $renderer = new XmlErrorRenderer();

        /** @var stdClass $output */
        $output = simplexml_load_string($renderer->__invoke($exception, true));

        $this->assertSame((string) $output->message[0], 'Slim Application Error');
        $this->assertSame((string) $output->exception[0]->type, 'Exception');
        $this->assertSame((string) $output->exception[0]->message, 'Ooops...');
        $this->assertSame((string) $output->exception[1]->type, 'RuntimeException');
        $this->assertSame((string) $output->exception[1]->message, 'Oops..');
    }

    public function testXMLErrorRendererRenderHttpException()
    {
        $exceptionTitle = 'title';

        $httpExceptionProphecy = $this->prophesize(HttpException::class);

        $httpExceptionProphecy
            ->getTitle()
            ->willReturn($exceptionTitle)
            ->shouldBeCalledOnce();

        $renderer = new XmlErrorRenderer();

        /** @var stdClass $output */
        $output = simplexml_load_string($renderer->__invoke($httpExceptionProphecy->reveal(), true));

        $this->assertSame((string) $output->message[0], $exceptionTitle, 'Should contain http exception title');
    }

    public function testPlainTextErrorRendererFormatFragmentMethod()
    {
        $message = 'Oops.. <br>';
        $exception = new Exception($message, 500);
        $renderer = new PlainTextErrorRenderer();
        $reflectionRenderer = new ReflectionClass(PlainTextErrorRenderer::class);

        $method = $reflectionRenderer->getMethod('formatExceptionFragment');
        $this->setAccessible($method);
        $output = $method->invoke($renderer, $exception);
        $this->assertIsString($output);

        $this->assertMatchesRegularExpression('/.*Type:*/', $output);
        $this->assertMatchesRegularExpression('/.*Code:*/', $output);
        $this->assertMatchesRegularExpression('/.*Message*/', $output);
        $this->assertMatchesRegularExpression('/.*File*/', $output);
        $this->assertMatchesRegularExpression('/.*Line*/', $output);

        // ensure the renderer doesn't reformat the message
        $this->assertMatchesRegularExpression("/.*$message/", $output);
    }

    public function testPlainTextErrorRendererDisplaysErrorDetails()
    {
        $previousException = new RuntimeException('Oops..');
        $exception = new Exception('Ooops...', 0, $previousException);

        $renderer = new PlainTextErrorRenderer();
        $output = $renderer->__invoke($exception, true);

        $this->assertMatchesRegularExpression('/Ooops.../', $output);
    }

    public function testPlainTextErrorRendererNotDisplaysErrorDetails()
    {
        $previousException = new RuntimeException('Oops..');
        $exception = new Exception('Ooops...', 0, $previousException);

        $renderer = new PlainTextErrorRenderer();
        $output = $renderer->__invoke($exception, false);

        $this->assertSame("Slim Application Error\n", $output, 'Should show only one string');
    }

    public function testPlainTextErrorRendererRenderHttpException()
    {
        $exceptionTitle = 'title';

        $httpExceptionProphecy = $this->prophesize(HttpException::class);

        $httpExceptionProphecy
            ->getTitle()
            ->willReturn($exceptionTitle)
            ->shouldBeCalledOnce();

        $renderer = new PlainTextErrorRenderer();
        $output = $renderer->__invoke($httpExceptionProphecy->reveal(), true);

        $this->assertStringContainsString($exceptionTitle, $output, 'Should contain http exception title');
    }
}
