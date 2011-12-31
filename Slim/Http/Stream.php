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
 * Slim HTTP Stream
 *
 * This class substitutes for `Slim_Http_Response` when
 * `streamFile()`, `streamData()`, or `streamProcess()` is
 * invoked. At which time, the Slim app's response property
 * is set to an instance of `Slim_Http_Stream`.
 *
 * This class implements a `finalize()` and `write()` method
 * just like `Slim_Http_Response` (polymorphism) so that the framework's
 * expectations are still met.
 *
 * In the Slim app's `run()` method, if it encounters a response body
 * that is not a string, the response body object's `process()` method
 * is invoked; otherwise, Slim will `echo()` the string response body.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
class Slim_Http_Stream {
    /**
     * @var mixed This is a Slim_Stream_* instance that performs the actual streaming
     */
    protected $streamer;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor
     * @param   mixed $streamer
     * @param   array $options
     * @return  void
     */
    public function __construct( $streamer, $options ) {
        $this->streamer = $streamer;
        $this->options = array_merge(array(
            'name' => 'foo',
            'type' => 'application/octet-stream',
            'disposition' => 'attachment', //or "inline"
            'encoding' => 'binary', //or "ascii"
            'allow_cache' => false
        ), $options);
    }

    /**
     * Finalize
     * @return array[status, header, body]
     */
    public function finalize() {
        $headers = new Slim_Http_Headers();
        if ( $this->options['allow_cache'] ) {
            $headers['Pragma'] = 'public'; //Used by HTTP/1.0 clients
            $headers['Cache-Control'] = 'public';
        } else {
            $headers['Pragma'] = 'no-cache'; //Used by HTTP/1.0 clients
            $headers['Cache-Control'] = 'no-cache';
        }
        $headers['Content-Type'] = $this->options['type'];
        $headers['Content-Disposition'] = sprintf('%s; filename=%s', $this->options['disposition'], $this->options['name']);
        $headers['Content-Transfer-Encoding'] = $this->options['encoding'];
        return array(200, $headers, $this->streamer);
    }

    /**
     * Write
     *
     * This is a dummy method to satisfy the framework's expectations. In the future
     * some refactoring may allow this method to be removed. For now, carry on. Nothing
     * to see here folks.
     *
     * @return void
     */
    public function write( $body ) {}
}
