<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use Slim\Http\NonBufferedBody;

class NonBufferedBodyTest extends \PHPUnit_Framework_TestCase
{
    public function testTheStreamContract()
    {
        $body = new NonBufferedBody();

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
}
