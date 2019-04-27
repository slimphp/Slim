<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Http;

/**
 * Return the value of the global variable $GLOBALS['getallheaders_return'] if it exists. Otherwise the
 * function override calls the default php built-in function.
 *
 * @return array|false
 */
function getallheaders()
{
    if (array_key_exists('getallheaders_return', $GLOBALS)) {
        return $GLOBALS['getallheaders_return'];
    }

    return \getallheaders();
}
