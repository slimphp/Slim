<?php
namespace Slim\Behat\Context;

use Behat\Behat\Context\Context as BehatContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

class BasicSmokeTestContext implements BehatContext
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
}
