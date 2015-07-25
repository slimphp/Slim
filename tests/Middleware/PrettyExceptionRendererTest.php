<?php


use Slim\Middleware\PrettyExceptionRenderer;

class PrettyExceptionRendererTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testRender()
    {
        $exception = new \Exception("_message_",21221);
        $prettyPrint = PrettyExceptionRenderer::renderException($exception);
        $this->assertContains('Slim Application Error', $prettyPrint);
        $this->assertContains('21221', $prettyPrint);
        $this->assertContains("_message_", $prettyPrint);
    }
}
