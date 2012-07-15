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
 * Stream a PHP stream
 *
 * This class will stream an opened stream to the HTTP client.
 * You may set options by passing an associative array
 * of settings as the second argument to the constructor. They are:
 *
 * 1) buffer_size - The size of each chunk
 * 2) time_limit - The amount of time allowed
 *
 * @package Slim
 * @author  Hans-Peter Oeri <hp@oeri.ch>
 * @since   1.6.0
 */
class Slim_Stream_Stream implements Slim_StreamInterface {

	/**
     * @var resource
     */
    protected $instream;

    /**
     * @var array
     */
    protected $options;

    /**
     * constructor
     * @param   resource  in        PHP stream to read
     * @param   array     inions    (optional) associative array of streaming settings
     */
    public function __construct( $in, $options = array() ) {
        if ( !is_resource($in) ) {
            throw new InvalidArgumentException('Expect PHP stream to read');
        }
        $this->instream = $in;
        $this->options += array(
            'buffer_size' => 8192,
            'time_limit' => 0
        );
    }

    /**
     * send the stream to the client
     */
    public function process() {
        set_time_limit($this->options['time_limit']);
        $in = $this->instream;
        if ( PHP_VERSION_ID >= 50303 ) {
            stream_set_read_buffer($in, $this->options['buffer_size']);
        }
        $out = fopen( 'php://output', 'w' );
        stream_set_write_buffer($out, $this->options['buffer_size']);
        stream_copy_to_stream($in, $out);
        flush();
        ob_flush();
        fclose($in);
        fclose($out);
    }
}
