<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use ReflectionClass;
use Slim\ResponseEmitter;
use Slim\Tests\Assets\HeaderStack;
use Slim\Tests\Mocks\MockStream;
use Slim\Tests\Mocks\SlowPokeStream;
use Slim\Tests\Mocks\SmallChunksStream;

use function base64_decode;
use function fopen;
use function fwrite;
use function in_array;
use function popen;
use function rewind;
use function str_repeat;
use function stream_filter_append;
use function stream_filter_remove;
use function stream_get_filters;
use function strlen;
use function trim;

use const CONNECTION_ABORTED;
use const CONNECTION_TIMEOUT;
use const STREAM_FILTER_READ;
use const STREAM_FILTER_WRITE;

class ResponseEmitterTest extends TestCase
{
    public function setUp(): void
    {
        HeaderStack::reset();
    }

    public function tearDown(): void
    {
        HeaderStack::reset();
    }

    public function testRespond(): void
    {
        $response = $this->createResponse();
        $response->getBody()->write('Hello');

        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);

        $this->expectOutputString('Hello');
    }

    public function testRespondWithPaddedStreamFilterOutput(): void
    {
        $availableFilter = stream_get_filters();

        $filterName = 'string.rot13';
        $unfilterName = 'string.rot13';
        $specificFilterName = 'string.rot13';
        $specificUnfilterName = 'string.rot13';

        if (in_array($filterName, $availableFilter) && in_array($unfilterName, $availableFilter)) {
            $key = base64_decode('xxxxxxxxxxxxxxxx');
            $iv = base64_decode('Z6wNDk9LogWI4HYlRu0mng==');

            $data = 'Hello';
            $length = strlen($data);

            $stream = fopen('php://temp', 'r+');
            $filter = stream_filter_append($stream, $specificFilterName, STREAM_FILTER_WRITE, [
                'key' => $key,
                'iv' => $iv
            ]);

            fwrite($stream, $data);
            rewind($stream);
            stream_filter_remove($filter);
            stream_filter_append($stream, $specificUnfilterName, STREAM_FILTER_READ, [
                'key' => $key,
                'iv' => $iv
            ]);

            $body = $this->getStreamFactory()->createStreamFromResource($stream);
            $response = $this
                ->createResponse()
                ->withHeader('Content-Length', $length)
                ->withBody($body);

            $responseEmitter = new ResponseEmitter();
            $responseEmitter->emit($response);

            $this->expectOutputString('Hello');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testRespondIndeterminateLength(): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'Hello');
        rewind($stream);

        $body = $this
            ->getMockBuilder(MockStream::class)
            ->setConstructorArgs([$stream])
            ->onlyMethods(['getSize'])
            ->getMock();
        $body->method('getSize')->willReturn(null);

        $response = $this->createResponse()->withBody($body);
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);

        $this->expectOutputString('Hello');
    }

    public function testResponseWithStreamReadYieldingLessBytesThanAsked(): void
    {
        $body = new SmallChunksStream();
        $response = $this->createResponse()->withBody($body);

        $responseEmitter = new ResponseEmitter($body::CHUNK_SIZE * 2);
        $responseEmitter->emit($response);

        $this->expectOutputString(str_repeat('.', $body->getSize()));
    }

    public function testResponseReplacesPreviouslySetHeaders(): void
    {
        $response = $this
            ->createResponse(200, 'OK')
            ->withHeader('X-Foo', 'baz1')
            ->withAddedHeader('X-Foo', 'baz2');
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);

        $expectedStack = [
            ['header' => 'X-Foo: baz1', 'replace' => true, 'status_code' => null],
            ['header' => 'X-Foo: baz2', 'replace' => false, 'status_code' => null],
            ['header' => 'HTTP/1.1 200 OK', 'replace' => true, 'status_code' => 200],
        ];

        $this->assertSame($expectedStack, HeaderStack::stack());
    }

    public function testResponseDoesNotReplacePreviouslySetSetCookieHeaders(): void
    {
        $response = $this
            ->createResponse(200, 'OK')
            ->withHeader('set-cOOkie', 'foo=bar')
            ->withAddedHeader('Set-Cookie', 'bar=baz');
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);

        $expectedStack = [
            ['header' => 'set-cOOkie: foo=bar', 'replace' => false, 'status_code' => null],
            ['header' => 'set-cOOkie: bar=baz', 'replace' => false, 'status_code' => null],
            ['header' => 'HTTP/1.1 200 OK', 'replace' => true, 'status_code' => 200],
        ];

        $this->assertSame($expectedStack, HeaderStack::stack());
    }

    public function testIsResponseEmptyWithNonEmptyBodyAndTriggeringStatusCode(): void
    {
        $body = $this->createStream('Hello');
        $response = $this
            ->createResponse(204)
            ->withBody($body);
        $responseEmitter = new ResponseEmitter();

        $this->assertTrue($responseEmitter->isResponseEmpty($response));
    }

    public function testIsResponseEmptyDoesNotReadAllDataFromNonEmptySeekableResponse(): void
    {
        $body = $this->createStream('Hello');
        $response = $this
            ->createResponse(200)
            ->withBody($body);
        $responseEmitter = new ResponseEmitter();

        $responseEmitter->isResponseEmpty($response);

        $this->assertTrue($body->isSeekable());
        $this->assertFalse($body->eof());
    }

    public function testIsResponseEmptyDoesNotDrainNonSeekableResponseWithContent(): void
    {
        $resource = popen('echo 12', 'r');
        $body = $this->getStreamFactory()->createStreamFromResource($resource);
        $response = $this->createResponse(200)->withBody($body);
        $responseEmitter = new ResponseEmitter();

        $responseEmitter->isResponseEmpty($response);

        $this->assertFalse($body->isSeekable());
        $this->assertSame('12', trim((string) $body));
    }

    public function testAvoidReadFromSlowStreamAccordingToStatus(): void
    {
        $body = new SlowPokeStream();
        $response = $this
            ->createResponse(204, 'No content')
            ->withBody($body);

        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);

        $this->assertFalse($body->eof());
        $this->expectOutputString('');
    }

    public function testIsResponseEmptyWithEmptyBody(): void
    {
        $response = $this->createResponse(200);
        $responseEmitter = new ResponseEmitter();

        $this->assertTrue($responseEmitter->isResponseEmpty($response));
    }

    public function testIsResponseEmptyWithZeroAsBody(): void
    {
        $body = $this->createStream('0');
        $response = $this
            ->createResponse(200)
            ->withBody($body);
        $responseEmitter = new ResponseEmitter();

        $this->assertFalse($responseEmitter->isResponseEmpty($response));
    }

    public function testWillHandleInvalidConnectionStatusWithADeterminateBody(): void
    {
        $body = $this->getStreamFactory()->createStreamFromResource(fopen('php://temp', 'r+'));
        $body->write('Hello!' . "\n");
        $body->write('Hello!' . "\n");

        // Tell connection_status() to fail.
        $GLOBALS['connection_status_return'] = CONNECTION_ABORTED;

        $response = $this
            ->createResponse()
            ->withHeader('Content-Length', $body->getSize())
            ->withBody($body);

        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);

        $this->expectOutputString("Hello!\nHello!\n");

        // Tell connection_status() to pass.
        unset($GLOBALS['connection_status_return']);
    }

    public function testWillHandleInvalidConnectionStatusWithAnIndeterminateBody(): void
    {
        $body = $this->getStreamFactory()->createStreamFromResource(fopen('php://input', 'r+'));

        // Tell connection_status() to fail.
        $GLOBALS['connection_status_return'] = CONNECTION_TIMEOUT;

        $response = $this
            ->createResponse()
            ->withBody($body);


        $responseEmitter = new ResponseEmitter();

        $mirror = new ReflectionClass(ResponseEmitter::class);
        $emitBodyMethod = $mirror->getMethod('emitBody');
        $this->setAccessible($emitBodyMethod);
        $emitBodyMethod->invoke($responseEmitter, $response);

        $this->expectOutputString("");

        // Tell connection_status() to pass.
        unset($GLOBALS['connection_status_return']);
    }
}
