<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class ErrorRendererTest implements ErrorRendererInterface
{
    /**
     * {@inheritdoc}
     */
    public function render(Throwable $exception, bool $displayErrorDetails): string
    {
        return $exception->getMessage().($displayErrorDetails ? ' +Details' : '');
    }
}
