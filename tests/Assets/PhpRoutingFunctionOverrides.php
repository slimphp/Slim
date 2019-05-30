<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim
{
    function connection_status($toggleFailure = false)
    {
        static $shouldFail = false;

        if ($shouldFail) {
            return CONNECTION_ABORTED;
        }

        if ($shouldFail !== $toggleFailure) {
            $shouldFail = $toggleFailure;
        }

        return \connection_status();
    }
}

namespace Slim\Routing
{
    /**
     * Is a file descriptor writable
     *
     * @param $file
     * @return bool
     */
    function is_readable($file)
    {
        if (stripos($file, 'non-readable.cache') !== false) {
            return false;
        }
        return true;
    }

    /**
     * Is a path writable
     *
     * @param $path
     * @return bool
     */
    function is_writable($path)
    {
        if (stripos($path, 'non-writable-directory') !== false) {
            return false;
        }
        return true;
    }
}
