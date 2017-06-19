<?php
namespace Slim\Behat\Context;

use Behat\Behat\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Http\Uri;

class FeatureContext implements Context
{
    /**
     * @var App
     */
    private $application;

    public function __construct(App $app)
    {
        $this->application = $app;
    }

    /**
     * @When I query the route :uri
     */
    public function iQueryTheRoute($uri)
    {
        $request = $this->getRequest();
        $request = $request->withUri(Uri::createFromString('http://localhost/' . $uri))->withMethod('GET');
        $this->application->process($request, $this->getResponse());
    }

    /**
     * @Then the response body should be :expectedResponseBody
     */
    public function theResponseBodyShouldBe($expectedResponseBody)
    {
        $body = $this->getResponse()->getBody();
        $body->rewind();
        $responseBody = $body->__toString();
        \PHPUnit\Framework\Assert::assertEquals($expectedResponseBody, $responseBody);
    }

    /**
     * @return ServerRequestInterface
     */
    private function getRequest()
    {
        return $this->application->getContainer()->get('request');
    }

    /**
     * @return ResponseInterface
     */
    private function getResponse()
    {
        return $this->application->getContainer()->get('response');
    }
}
