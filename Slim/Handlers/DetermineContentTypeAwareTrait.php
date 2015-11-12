<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

use Psr\Http\Message\ServerRequestInterface;

/**
 * DetermineContentType
 *
 * This is trait for simple determine content type from accept header of request.
 * To use full features, please using https://github.com/willdurand/Negotiation
 */
trait DetermineContentTypeAwareTrait
{
    /**
     * Known handled content types
     *
     * @var array
     */
    protected $knownContentTypes = [
        'application/json' => true,
        'application/xml' => true,
        'text/xml' => true,
        'text/html' => true,
    ];

    /**
     * Determine which content type we know about is wanted using Accept header
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function determineContentType(ServerRequestInterface $request)
    {
        $acceptHeader = $request->getHeaderLine('Accept');

        foreach (explode(',', $acceptHeader) as $accept) {
            if (isset($this->knownContentTypes[$accept])) {
                return $accept;
            }
        }

        return 'text/html';
    }
}