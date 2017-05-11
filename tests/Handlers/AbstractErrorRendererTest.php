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
use Slim\Exception\PhpException;
use Slim\Handlers\ErrorRenderers\HtmlErrorRenderer;
use Slim\Handlers\ErrorRenderers\JsonErrorRenderer;
use Slim\Handlers\ErrorRenderers\PlainTextErrorRenderer;
use Slim\Handlers\ErrorRenderers\XmlErrorRenderer;
use Exception;
use RuntimeException;

class AbstractErrorRendererTest extends TestCase
{
    public function testPlainTextErrorRenderDoesNotDisplayErrorDetails()
    {
        $exception = new Exception('Oops..');
        $renderer = new PlainTextErrorRenderer($exception, true);

        $this->assertEquals('Oops..', $renderer->render());
    }

    public function testHTMLErrorRendererOutputForPhpExceptions()
    {
        $exception = new Exception('Oops..');
        $genericRenderer = new HtmlErrorRenderer($exception);
        $genericOutput = $genericRenderer->render();

        $phpException = new PhpException(new RuntimeException('Oops..'));
        $phpExceptionRenderer = new HtmlErrorRenderer($phpException);
        $phpExceptionOutput = $phpExceptionRenderer->render();

        $this->assertNotEquals($genericOutput, $phpExceptionOutput);
        $this->assertRegExp('/.*Slim Application Error.*/', $phpExceptionOutput);
    }

    public function testHTMLErrorRendererDisplaysErrorDetails()
    {
        $exception = new PhpException(new RuntimeException('Oops..'));
        $renderer = new HtmlErrorRenderer($exception, true);
        $output = $renderer->render();

        $this->assertRegExp('/.*The application could not run because of the following error:.*/', $output);
    }

    public function testHTMLErrorRendererRenderFragmentMethod()
    {
        $exception = new Exception('Oops..', 500);
        $renderer = new HtmlErrorRenderer($exception, true);
        $output = $renderer->renderExceptionFragment($exception);

        $this->assertRegExp('/.*Type:*/', $output);
        $this->assertRegExp('/.*Code:*/', $output);
        $this->assertRegExp('/.*Message*/', $output);
        $this->assertRegExp('/.*File*/', $output);
        $this->assertRegExp('/.*Line*/', $output);
    }

    public function testJSONErrorRendererPhpOutputForPhpExceptions()
    {
        $exception = new PhpException(new RuntimeException('Oops..'));
        $renderer = new JsonErrorRenderer($exception);
        $output = $renderer->renderPhpExceptionOutput();
        $this->assertRegExp('/.*Slim Application Error.*/', $output);
    }

    public function testJSONErrorRendererDisplaysErrorDetails()
    {
        $exception = new Exception('Oops..');
        $renderer = new JsonErrorRenderer($exception, true);
        $fragment = $renderer->renderExceptionFragment($exception);
        $output = json_encode(json_decode($renderer->render()));
        $expectedString = json_encode(['message' => 'Oops..', 'exception' => [$fragment]]);

        $this->assertEquals($output, $expectedString);
    }

    public function testJSONErrorRendererDoesNotDisplayErrorDetails()
    {
        $exception = new Exception('Oops..');
        $renderer = new JsonErrorRenderer($exception);
        $output = json_encode(json_decode($renderer->render()));

        $this->assertEquals($output, json_encode(['message' => 'Oops..']));
    }

    public function testJSONErrorRendererDisplaysPreviousError()
    {
        $previousException = new Exception('Oh no!');
        $exception = new Exception('Oops..', 0, $previousException);
        $renderer = new JsonErrorRenderer($exception, true);
        $output = json_encode(json_decode($renderer->render()));

        $fragments = [
            $renderer->renderExceptionFragment($exception),
            $renderer->renderExceptionFragment($previousException),
        ];

        $expectedString = json_encode(['message' => 'Oops..', 'exception' => $fragments]);

        $this->assertEquals($output, $expectedString);
    }

    public function testXMLErrorRendererDisplaysErrorDetails()
    {
        $previousException = new RuntimeException('Oops..');
        $exception = new PhpException($previousException);
        $renderer = new XmlErrorRenderer($exception, true);
        $output = simplexml_load_string($renderer->render());

        $this->assertEquals($output->message[0], 'Slim Application Error');
        $this->assertEquals((string)$output->exception[0]->type, 'Slim\Exception\PhpException');
        $this->assertEquals((string)$output->exception[1]->type, 'RuntimeException');
    }
}
