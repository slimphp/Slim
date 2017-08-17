<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Http\Headers;

class HttpExceptionTest extends TestCase
{
    public function testThatConstructorWillSetDetailsAccordingly()
    {
        $exceptionWithMessage = new HttpNotAllowedException('Oops..');
        $this->assertEquals($exceptionWithMessage->getMessage(), 'Oops..');

        $details = ['allowedMethods' => 'POST'];
        $exceptionWithDetails = new HttpNotAllowedException($details);
        $this->assertEquals($exceptionWithDetails->getDetails(), $details);
    }

    public function testHttpExceptionRequestReponseGetterSetters()
    {
        // Prepare request and response objects
        $uri = Uri::createFromGlobals($_SERVER);
        $headers = Headers::createFromGlobals($_SERVER);
        $cookies = [];
        $serverParams = $_SERVER;
        $body = new RequestBody();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();
        $exception = new HttpNotFoundException;
        $exception->setRequest($request);
        $exception->setResponse($response);

        $this->assertInstanceOf(Request::class, $exception->getRequest());
        $this->assertInstanceOf(Response::class, $exception->getResponse());
    }

    public function testHttpExceptionAttributeGettersSetters()
    {
        $exception = new HttpNotFoundException;
        $exception->setTitle('Title');
        $exception->setDescription('Description');
        $exception->setDetails(['Details']);

        $this->assertEquals('Title', $exception->getTitle());
        $this->assertEquals('Description', $exception->getDescription());
        $this->assertEquals(['Details'], $exception->getDetails());
    }

    public function testHttpNotAllowedExceptionGetAllowedMethods()
    {
        $exception = new HttpNotAllowedException;
        $exception->setAllowedMethods('GET');
        $this->assertEquals('GET', $exception->getAllowedMethods());

        $exception = new HttpNotAllowedException;
        $this->assertEquals('', $exception->getAllowedMethods());
    }
}
