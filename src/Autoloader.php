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
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
 */
class Autoloader
{
    /**
     * @var array $map namespace prefix to source location mapping
     */
    public static $map = [];
    
    /**
     * Load class
     * 
     * @param string $class Class to load
     */
    public static function autoload($class)
    {
        foreach (self::$map as $prefix => $source) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            $relativeClass = substr($class, $len);
            $fileName      = $source . str_replace('\\', '/', $relativeClass) . '.php';

            // Require file only if it exists. Else let other registered autoloaders worry about it.
            if (file_exists($fileName)) {
                require $fileName;
                break;
            }
        }
    }

    /**
     * Register autoloader
     * 
     * @param array $map namespace prefix to source location mapping
     */
    public static function register(array $map = [])
    {
        static $registered = false;
        
        // use merge, because pubic static $map could already have a value.
        self::$map = array_merge(self::$map, $map);
        
        if (!$registered) {
            spl_autoload_register(__NAMESPACE__ . "\\Autoloader::autoload");
            $registered = true;
        }
    }
}