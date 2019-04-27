<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Psr\Http\Message\StreamInterface;
use Slim\Http\Message;
use Slim\Interfaces\Http\HeadersInterface;

class MessageStub extends Message
{
    /**
     * Protocol version
     *
     * @var string
     */
    public $protocolVersion;

    /**
     * Headers
     *
     * @var HeadersInterface
     */
    public $headers;

    /**
     * Body object
     *
     * @var StreamInterface
     */
    public $body;
}
