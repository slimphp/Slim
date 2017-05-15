<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Http;

/**
 * Provides a PSR-7 implementation of a reusable raw request body
 */
class RequestBody extends Body
{
    /**
     * Create a new RequestBody.
     *
     * @param Environment $environment The Slim application Environment
     */
    public function __construct(Environment $environment = null)
    {
        $stream = fopen('php://temp', 'w+');
        if ($environment != null && $environment["MOCK_POST_DATA"] != null) {
            fwrite($stream, $environment["MOCK_POST_DATA"]);
        } else {
            stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        }
        rewind($stream);

        parent::__construct($stream);
    }
}
