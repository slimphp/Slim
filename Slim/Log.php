<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart
 * @link        http://www.slimframework.com
 * @copyright   2011 Josh Lockhart
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Log Adapter
 *
 * This is an adapter for your own custom Logger. This adapter assumes
 * your custom Logger provides the following public instance methods:
 *
 * debug( mixed $object )
 * info( mixed $object )
 * warn( mixed $object )
 * error( mixed $object )
 * fatal( mixed $object )
 *
 * USE THIS ADAPTER CLASS TO LOG ACTIVITY IN YOUR SLIM APPLICATION.
 * DO NOT CALL YOUR OWN LOGGER DIRECTLY.
 *
 * This class assumes nothing else about your custom Logger, so you are free
 * to use Apache's Log4PHP logger or any other log class that, at the
 * very least, implements the five public instance methods shown above.
 *
 * @package Slim
 * @author  Josh Lockhart <info@joshlockhart.com>
 * @since   Version 1.0
 */
class Slim_Log {

    /**
     * @var mixed An object that implements expected Logger interface
     */
    private static $logger;

    /**
     * Log debug message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public static function debug( $object ) {
        return isset(self::$logger) ? self::$logger->debug($object) : false;
    }

    /**
     * Log info message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public static function info( $object ) {
        return isset(self::$logger) ? self::$logger->info($object) : false;
    }

    /**
     * Log warn message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public static function warn( $object ) {
        return isset(self::$logger) ? self::$logger->warn($object) : false;
    }

    /**
     * Log error message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public static function error( $object ) {
        return isset(self::$logger) ? self::$logger->error($object) : false;
    }

    /**
     * Log fatal message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public static function fatal( $object ) {
        return isset(self::$logger) ? self::$logger->fatal($object) : false;
    }

    /**
     * Set the Logger object
     *
     * @param   mixed   $logger Instance of your custom Logger
     * @return  void
     */
    public static function setLogger( $logger ) {
        self::$logger = $logger;
    }

    /**
     * Get the Logger object
     *
     * @return  mixed   Instance of your custom Logger
     */
    public static function getLogger() {
        return self::$logger;
    }

}

