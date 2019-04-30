<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Http;

use PHPUnit_Framework_TestCase;
use Slim\Http\NonBufferedBody;
use Slim\Http\Response;
use Slim\Tests\Assets\HeaderStack;

class NonBufferedBodyTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        HeaderStack::reset();
    }

    protected function tearDown()
    {
        HeaderStack::reset();
    }

    public function testTheStreamContract()
    {
        $body = new NonBufferedBody();
        $body->close();
        $body->seek(0);
        $body->rewind();

        self::assertSame('', (string) $body, 'Casting to string returns no data, since the class does not store any');
        self::assertNull($body->detach(), 'Returns null since there is no such underlying stream');
        self::assertNull($body->getSize(), 'Current size is undefined');
        self::assertSame(0, $body->tell(), 'Pointer is considered to be at position 0 to conform');
        self::assertTrue($body->eof(), 'Always considered to be at EOF');
        self::assertFalse($body->isSeekable(), 'Cannot seek');
        self::assertTrue($body->isWritable(), 'Body is writable');
        self::assertFalse($body->isReadable(), 'Body is not readable');
        self::assertSame('', $body->read(10), 'Data cannot be retrieved once written');
        self::assertSame('', $body->getContents(), 'Data cannot be retrieved once written');
        self::assertNull($body->getMetadata(), 'Metadata mechanism is not implemented');
    }

    public function testWithHeader()
    {
        (new Response())
            ->withBody(new NonBufferedBody())
            ->withHeader('Foo', 'Bar');

        self::assertSame([
            [
                'header' => 'Foo: Bar',
                'replace' => true,
                'status_code' => null
            ]
        ], HeaderStack::stack());
    }

    public function testWithAddedHeader()
    {
        (new Response())
            ->withBody(new NonBufferedBody())
            ->withHeader('Foo', 'Bar')
            ->withAddedHeader('Foo', 'Baz');

        self::assertSame([
            [
                'header' => 'Foo: Bar',
                'replace' => true,
                'status_code' => null
            ],
            [
                'header' => 'Foo: Bar,Baz',
                'replace' => true,
                'status_code' => null
            ]
        ], HeaderStack::stack());
    }


    public function testWithoutHeader()
    {
        (new Response())
            ->withBody(new NonBufferedBody())
            ->withHeader('Foo', 'Bar')
            ->withoutHeader('Foo');

        self::assertSame([], HeaderStack::stack());
    }
}
