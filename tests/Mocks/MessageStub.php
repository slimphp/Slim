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
use Slim\Interfaces\Http\HeadersInterface;
use Psr\Http\Message\StreamInterface;

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
