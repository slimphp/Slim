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
 * Stream Process Output
 *
 * This class will stream process output to the HTTP client.
 * You may control how the output is streamed by passing an associative array
 * of settings as the second argument to the constructor. They are:
 *
 * 1) time_limit - The amount of time allowed to stream the data
 *
 * By default, PHP will run indefinitely until the output streaming is complete
 * or the client closes the HTTP connection. Unlike Slim_Stream_File,
 * this class will stream output line by line as it is returned from
 * the system process.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
class Slim_Stream_Process {
    /**
     * @var string
     */
    protected $process;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor
     * @param   string  $process    The system process to execute; BE SURE YOU ESCAPE SHELL ARGS!
     * @param   array   $options    Optional associative array of streaming settings
     * @return  void
     */
    public function __construct( $process, $options = array() ) {
        $this->process = (string)$process;
        $this->options = array_merge(array( 
            'time_limit' => 0
        ), $options);
    }

    /**
     * Process
     *
     * This method initiates the process output stream to the HTTP client. Buffered
     * content is continually and immediately flushed. Use the `time_limit`
     * setting if you want to set a finite timeout for large output; otherwise
     * the script is configured to run indefinitely until all output is sent.
     *
     * Unlike Slim_Http_File, data is flushed by line rather than in chunks.
     *
     * @return void
     */
    public function process() {
        set_time_limit($this->options['time_limit']);
        $handle = popen($this->process, 'r');
        if ( $handle ) {
            while ( ($data = fgets($handle)) !== false && connection_status() === 0 ) {
                echo $data;
                ob_flush();
                flush();
            }
            pclose($handle);
        }
    }
}
