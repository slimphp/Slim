<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Slim\Error\AbstractErrorRenderer;

class MockErrorRenderer extends AbstractErrorRenderer
{
    /**
     * @param \Throwable $exception
     * @param bool       $displayErrorDetails
     * @return string
     */
    public function __invoke(\Throwable $exception, bool $displayErrorDetails): string
    {
        return '';
    }
}
