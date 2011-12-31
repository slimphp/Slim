<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.0
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
 * Stream File
 *
 * This class will stream a file from your file system to the HTTP client.
 * You may control how the file is streamed by passing an associative array
 * of settings as the second argument to the constructor. They are:
 *
 * 1) buffer_size - The size of each streamed file chunk
 * 2) time_limit - The amount of time allowed to stream the file
 *
 * By default, PHP will run indefinitely until the file streaming is complete
 * or the client closes the HTTP connection. The chunk size is 8192 bytes
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
class Slim_Stream_File {
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor
     * @param   string  $path       Relative or absolute path to readable file
     * @param   array   $options    Optional associative array of streaming settings
     * @return  void
     * @throws  InvalidArgumentException If file does not exist or is not readable
     */
    public function __construct( $path, $options = array() ) {
        if ( !is_file($path) ) {
            throw new InvalidArgumentException('Cannot stream file. File does not exist.');
        }
        if ( !is_readable($path) ) {
            throw new InvalidArgumentException('Cannot stream file. File is not readable.');
        }
        $this->path = $path;
        $this->options = array_merge(array( 
            'buffer_size' => 8192,
            'time_limit' => 0
        ), $options);
    }

    /**
     * Process
     *
     * This method initiates the file stream to the HTTP client. Buffered
     * content is continually and immediately flushed. Use the `time_limit`
     * setting if you want to set a finite timeout for large files; otherwise
     * the script is configured to run indefinitely until the entire file is sent.
     *
     * @return void
     */
    public function process() {
        set_time_limit($this->options['time_limit']);
        $handle = fopen($this->path, 'rb');
        if ( $handle ) {
            while ( !feof($handle) && connection_status() === 0 ) {
                $buffer = fread($handle, $this->options['buffer_size']);
                if ( $buffer ) {
                    echo $buffer;
                    ob_flush();
                    flush();
                }
            }
            fclose($handle);
        }
    }
}
