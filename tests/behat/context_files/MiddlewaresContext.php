<?php
namespace Slim\Behat\Context;

use Behat\Behat\Context\Context as BehatContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Http\Uri;

class MiddlewaresContext implements BehatContext
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
     * @Given I have set up an application to display :stringToEcho
     */
    public function iHaveSetUpAnApplicationDisplay($stringToEcho)
    {
        $this->application->get(
            '/middleware-test',
            function (ServerRequestInterface $request, ResponseInterface $response) use ($stringToEcho) {
                $response->getBody()->write($stringToEcho);
                return $response;
            }
        );
    }

    /**
     * @Given I have added a middleware closure that adds :arg1 to the end of the output
     */
    public function iHaveAddedAMiddlewareClosureThatAddsToTheEndOfTheOutput($stringToAppend)
    {
        $middleware = function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next
        ) use ($stringToAppend) {
            $response = $next($request, $response);
            $response->getBody()->write($stringToAppend);
            return $response;
        };

        $this->application->add($middleware);
    }

    /**
     * @When I query the relevant route
     */
    public function iQueryTheRelevantRoute()
    {
        $response = $this->application->getContainer()->get('response');
        $request = $this->application->getContainer()->get('request');
        $request = $request->withUri(Uri::createFromString('http://localhost/middleware-test'))->withMethod('GET');
        $this->application->process($request, $response);
    }
}
