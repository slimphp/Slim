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
    protected $logger;

    /**
     * @var bool Enable logging?
     */
    protected $enabled;

    /**
     * Constructor
     *
     */
    public function __construct() {
        $this->enabled = true;
    }

    /**
     * Enable or disable logging
     *
     * @param bool
     * @return void
     */
    public function setEnabled( $enabled ) {
        if ( $enabled ) {
            $this->enabled = true;
        } else {
            $this->enabled = false;
        }
    }

    /**
     * Is logging enabled?
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * Log debug message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public function debug( $object ) {
        return isset($this->logger) && $this->isEnabled() ? $this->logger->debug($object) : false;
    }

    /**
     * Log info message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public function info( $object ) {
        return isset($this->logger) && $this->isEnabled() ? $this->logger->info($object) : false;
    }

    /**
     * Log warn message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public function warn( $object ) {
        return isset($this->logger) && $this->isEnabled() ? $this->logger->warn($object) : false;
    }

    /**
     * Log error message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public function error( $object ) {
        return isset($this->logger) && $this->isEnabled() ? $this->logger->error($object) : false;
    }

    /**
     * Log fatal message
     *
     * @param   mixed   $object
     * @return  mixed   What the Logger returns, or false if Logger not set
     */
    public function fatal( $object ) {
        return isset($this->logger) && $this->isEnabled() ? $this->logger->fatal($object) : false;
    }

    /**
     * Set Logger
     *
     * @param   Slim_Logger   $logger Instance of your custom Logger
     * @return  void
     */
    public function setLogger( $logger ) {
        $this->logger = $logger;
    }

    /**
     * Get Logger
     *
     * @return Slim_Logger Instance of your custom Logger
     */
    public function getLogger() {
        return $this->logger;
    }

}