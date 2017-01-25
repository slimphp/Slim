<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use Slim\Http\Message;

/**
 * Mock object for Slim\Http\MessageTest
 */
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
     * @var \Slim\Interfaces\Http\HeadersInterface
     */
    public $headers;

    /**
     * Body object
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    public $body;
}
