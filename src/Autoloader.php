<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

/**
 * Autoloader
 *
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 */
class Autoloader
{
    public static function autoload($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        // Require file only if it exists. Else let other registered autoloaders worry about it.
        if (file_exists($fileName)) {
            require $fileName;
        }
    }

    public static function register()
    {
        spl_autoload_register(__NAMESPACE__ . "\\Autoloader::autoload");
    }
}
