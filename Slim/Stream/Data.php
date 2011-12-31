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
 * Stream Data
 *
 * This class will stream raw data  to the HTTP client.
 * You may control how the data is streamed by passing an associative array
 * of settings as the second argument to the constructor. They are:
 *
 * 1) buffer_size - The size of each streamed data chunk
 * 2) time_limit - The amount of time allowed to stream the data
 *
 * By default, PHP will run indefinitely until the data streaming is complete
 * or the client closes the HTTP connection. The chunk size is 8192 bytes
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
class Slim_Stream_Data {
    /**
     * @var string
     */
    protected $data;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor
     * @param   string  $data       The raw data to be streamed to the HTTP client
     * @param   array   $options    Optional associative array of streaming settings
     * @return  void
     */
    public function __construct( $data, $options = array() ) {
        $this->data = (string)$data;
        $this->options = array_merge(array( 
            'buffer_size' => 8192,
            'time_limit' => 0
        ), $options);
    }

    /**
     * Process
     *
     * This method initiates the data stream to the HTTP client. Buffered
     * content is continually and immediately flushed. Use the `time_limit`
     * setting if you want to set a finite timeout for large data; otherwise
     * the script is configured to run indefinitely until all data is sent.
     *
     * Data will be base64 encoded into memory and streamed in chunks to
     * the HTTP client.
     *
     * @return void
     */
    public function process() {
        set_time_limit($this->options['time_limit']);
        $handle = fopen('data:text/plain;base64,' . base64_encode($this->data), 'rb');
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
