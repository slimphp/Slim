<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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

    /**
     * Constructor
     * @param   string  $directory  Absolute or relative path to log directory
     * @param   int     $level      The maximum log level reported by this Logger
     */
    public function __construct( $directory, $level = 4 ) {
        $this->setDirectory($directory);
        $this->setLevel($level);
    }

    /**
     * Set log directory
     * @param   string  $directory  Absolute or relative path to log directory
     * @return  void
     */
    public function setDirectory( $directory ) {
        $realPath = realpath($directory);
        if ( $realPath ) {
            $this->directory = rtrim($realPath, '/') . '/';
        } else {
            $this->directory = false;
        }
    }

    /**
     * Get log directory
     * @return string|false Absolute path to log directory with trailing slash
     */
    public function getDirectory() {
        return $this->directory;
    }

    /**
     * Set log level
     * @param   int                         The maximum log level reported by this Logger
     * @return  void
     * @throws  InvalidArgumentException    If level specified is not 0, 1, 2, 3, 4
     */
    public function setLevel( $level ) {
        $theLevel = (int)$level;
        if ( $theLevel >= 0 && $theLevel <= 4 ) {
            $this->level = $theLevel;
        } else {
            throw new InvalidArgumentException('Invalid Log Level. Must be one of: 0, 1, 2, 3, 4.');
        }
    }

    /**
     * Get log level
     * @return int
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * Log debug data
     * @param   mixed $data
     * @return  void
     */
    public function debug( $data ) {
        $this->log($data, 4);
    }

    /**
     * Log info data
     * @param   mixed $data
     * @return  void
     */
    public function info( $data ) {
        $this->log($data, 3);
    }

    /**
     * Log warn data
     * @param   mixed $data
     * @return  void
     */
    public function warn( $data ) {
        $this->log($data, 2);
    }

    /**
     * Log error data
     * @param   mixed $data
     * @return  void
     */
    public function error( $data ) {
        $this->log($data, 1);
    }

    /**
     * Log fatal data
     * @param   mixed $data
     * @return  void
     */
    public function fatal( $data ) {
        $this->log($data, 0);
    }

    /**
     * Get absolute path to current daily log file
     * @return string
     */
    public function getFile() {
        return $this->getDirectory() . strftime('%Y-%m-%d') . '.log';
    }

    /**
     * Log data to file
     * @param   mixed               $data
     * @param   int                 $level
     * @return  void
     * @throws  RuntimeException    If log directory not found or not writable
     */
    protected function log( $data, $level ) {
        $dir = $this->getDirectory();
        if ( $dir == false || !is_dir($dir) ) {
            throw new RuntimeException("Log directory '$dir' invalid.");
        }
        if ( !is_writable($dir) ) {
            throw new RuntimeException("Log directory '$dir' not writable.");
        }
        if ( $level <= $this->getLevel() ) {
            $this->write(sprintf("[%s] %s - %s\r\n", $this->levels[$level], date('c'), (string)$data));
        }
    }

    /**
     * Persist data to log
     * @param   string Log message
     * @return  void
     */
    protected function write( $data ) {
        @file_put_contents($this->getFile(), $data, FILE_APPEND | LOCK_EX);
    }

}