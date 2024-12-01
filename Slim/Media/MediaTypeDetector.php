<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Media;

use Psr\Http\Message\ServerRequestInterface;

use function explode;
use function strtolower;

/**
 * Detects the media types from an HTTP request, either from the 'Accept' header
 * or as a fallback from the 'Content-Type' header.
 */
final class MediaTypeDetector
{
    /**
     * Determine the desired content types from the 'Accept' header,
     * or fallback to 'Content-Type' if 'Accept' is empty.
     *
     * https://www.iana.org/assignments/media-types/media-types.xhtml
     */
    public function detect(ServerRequestInterface $request): array
    {
        $mediaTypes = $this->parseAcceptHeader($request->getHeaderLine('Accept'));

        if (!$mediaTypes) {
            $mediaTypes = $this->parseContentType($request->getHeaderLine('Content-Type'));
        }

        return $mediaTypes;
    }

    /**
     * Parses the 'Accept' header to extract media types.
     *
     * This method splits the 'Accept' header value into its components and normalizes
     * the media types by trimming whitespace and converting them to lowercase.
     *
     * This method doesn't consider the quality values (q-values) that can be present in the Accept header.
     * If prioritization is important for your use case, you might want to consider implementing
     * q-value parsing and sorting.
     *
     * @param string|null $accept the value of the 'Accept' header
     *
     * @return array an array of normalized media types from the 'Accept' header
     */
    private function parseAcceptHeader(?string $accept): array
    {
        $acceptTypes = $accept ? explode(',', $accept) : [];

        // Normalize types
        $cleanTypes = [];
        foreach ($acceptTypes as $type) {
            $tokens = explode(';', $type);
            $name = trim(strtolower(reset($tokens)));
            $cleanTypes[] = $name;
        }

        return $cleanTypes;
    }

    /**
     * Parses the 'Content-Type' header to extract the media type.
     *
     * This method splits the 'Content-Type' header value to separate the media type
     * from any additional parameters, normalizes it, and returns it in an array.
     *
     * @param string|null $contentType the value of the 'Content-Type' header
     *
     * @return array an array containing the normalized media type from the 'Content-Type' header
     */
    private function parseContentType(?string $contentType): array
    {
        if ($contentType === null) {
            return [];
        }

        $parts = explode(';', $contentType);
        $name = strtolower(trim($parts[0]));

        return $name ? [$name] : [];
    }
}
