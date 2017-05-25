<?php
namespace Slim\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context as BehatContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;

class Context implements BehatContext
{
    /**
     * @var App
     */
    private $application;

    /**
     * @var ResponseInterface
     */
    private $responseRetrieved;

    public function __construct()
    {
        $this->application = new App();
    }

    /**
     * @Given I have set up an application to return a response with Hello World
     */
    public function iHaveSetUpAnApplicationToReturnAResponseWithHelloWorld()
    {
        $this->application->get(
            '/hello-world',
            function (ServerRequestInterface $request, ResponseInterface $response) {
                $response->getBody()->write('Hello world');
                return $response;
            }
        );
    }

    /**
     * @Given I have set up an application to echo Hello World
     */
    public function iHaveSetUpAnApplicationToEchoHelloWorld()
    {
        $this->application->get(
            '/hello-world',
            function (ServerRequestInterface $request, ResponseInterface $response) {
                echo 'Hello world';
            }
        );
    }

    /**
     * @When I query the :uri route
     */
    public function iQueryTheRoute($uri)
    {
        $request = $this->getRequest();
        $request = $request->withUri(Uri::createFromString('http://localhost/' . $uri))->withMethod('GET');
        $this->responseRetrieved = $this->application->process($request, $this->getResponse());
    }

    /**
     * @Then the response body should be :expectedResponseBody
     */
    public function theResponseBodyShouldBe($expectedResponseBody)
    {
        $responseBody = $this->responseRetrieved->getBody()->__toString();
        \PHPUnit\Framework\Assert::assertEquals($expectedResponseBody, $responseBody);
    }

    /**
     * @return Request
     */
    private function getRequest()
    {
        return $this->application->getContainer()->get('request');
    }

    /**
     * @return Response
     */
    private function getResponse()
    {
        return $this->application->getContainer()->get('response');
    }
}
