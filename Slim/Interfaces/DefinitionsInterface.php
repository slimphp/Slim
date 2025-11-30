<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

/**
 * Interface for providing DI container service definitions.
 */
interface DefinitionsInterface
{
    /**
     * Return service definitions.
     *
     * @return array<string, mixed>
     */
    public function getDefinitions(): array;
}
