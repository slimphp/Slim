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
 * Logger
 *
 * A simple Logger that writes to a daily-unique log file in
 * a user-specified directory. By default, this class will write log
 * messages for all log levels; the log level may be changed to filter
 * unwanted log messages from the log file.
 *
 * @package Slim
 * @author  Josh Lockhart <info@joshlockhart.com>
 * @since   Version 1.0
 */
class Slim_Logger {

    /***** INSTANCES PROPERTIES *****/

    /**
     * @var array Log levels
     */
    protected $levels = array(
        0 => 'FATAL',
        1 => 'ERROR',
        2 => 'WARN',
        3 => 'INFO',
        4 => 'DEBUG'
    );

    /**
     * @var string Absolute path to log directory with trailing slash
     */
    protected $directory;

    /***** INSTANCE METHODS *****/

    /**
     * Constructor
     *
     * @param   string  $directory  Absolute or relative path to log directory
     * @param   int     $level      The maximum log level reported by this Logger
     */
    public function __construct( $directory, $level = 4 ) {
        $this->setDirectory($directory);
        $this->setLevel($level);
    }

    /**
     * Set log directory
     *
     * @param   string          $directory  Absolute or relative path to log directory
     * @return  void
     * @throws  RuntimeException            If log directory not found or not writable
     */
    public function setDirectory( $directory ) {
        $fullPath = realpath((string)$directory);
        if ( $fullPath === false || !is_dir($fullPath) ) {
            throw new RuntimeException("Log directory '$directory' invalid.");
        }
        if ( !is_writable($fullPath) ) {
            throw new RuntimeException("Log directory '$directory' not writable.");
        }
        $this->directory = rtrim($fullPath, '/') . '/';
    }

    /**
     * Get log directory
     *
     * @return string Absolute path to log directory with trailing slash
     */
    public function getDirectory() {
        return $this->directory;
    }

    /**
     * Set log level
     *
     * @param   int                         The maximum log level reported by this Logger
     * @return  void
     * @throws  InvalidArgumentException    If level specified is not 0, 1, 2, 3, 4
     */
    public function setLevel( $level ) {
        $theLevel = (int)$level;
        if ( $theLevel >= 0 && $theLevel <= 4 ) {
            $this->level = $theLevel;
        } else {
            throw new InvalidArgumentException("Invalid Log Level. Must be one of: 0, 1, 2, 3, 4.");
        }
    }

    /**
     * Get log level
     *
     * @return int
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * Log debug data
     *
     * @param   mixed $data
     * @return  void
     */
    public function debug( $data ) {
        $this->log($data, 4);
    }

    /**
     * Log info data
     *
     * @param   mixed $data
     * @return  void
     */
    public function info( $data ) {
        $this->log($data, 3);
    }

    /**
     * Log warn data
     *
     * @param   mixed $data
     * @return  void
     */
    public function warn( $data ) {
        $this->log($data, 2);
    }

    /**
     * Log error data
     *
     * @param   mixed $data
     * @return  void
     */
    public function error( $data ) {
        $this->log($data, 1);
    }

    /**
     * Log fatal data
     *
     * @param   mixed $data
     * @return  void
     */
    public function fatal( $data ) {
        $this->log($data, 0);
    }

    /**
     * Get absolute path to current daily log file
     *
     * @return string
     */
    protected function getFile() {
        return $this->getDirectory() . strftime('%Y-%m-%d') . '.log';
    }

    /**
     * Log data to file
     *
     * @param   mixed   $data
     * @param   int     $level
     * @return  void
     */
    protected function log( $data, $level ) {
        if ( $level <= $this->getLevel() ) {
            $this->write(sprintf("[%s] %s - %s\r\n", $this->levels[$level], date('c'), (string)$data));
        }
    }

    /**
     * Persist data to log
     *
     * @param   string Log message
     * @return  void
     */
    protected function write( $data ) {
        @file_put_contents($this->getFile(), $data, FILE_APPEND | LOCK_EX);
    }

}

