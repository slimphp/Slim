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
 * Abstract Session Handler
 *
 * This abstract class should be extended by each concrete
 * session handler. This class defines the contractual class interface
 * methods that must be implemented in concrete subclasses. This class
 * also provides the final `register` method used by Slim itself to
 * actually register the concrete session handler with PHP.
 *
 * @package Slim
 * @author Josh Lockhart
 * @since Version 1.3
 */
abstract class Slim_Session_Handler {

    /**
     * @var Slim
     */
    protected $app;

    /**
     * Register session handler
     *
     * @return bool
     */
    final public function register( Slim $app ) {
        $this->app = $app;
        return session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    /**
     * Open session
     *
     * @param string $savePath
     * @param string $sessionName
     * @return mixed
     */
    abstract public function open( $savePath, $sessionName );

    /**
     * Close session
     *
     * @return mixed
     */
    abstract public function close();

    /**
     * Read session data with ID
     *
     * @param string $id The session identifier
     * @return string
     */
    abstract public function read( $id );

    /**
     * Write session data with ID
     *
     * The "write" handler is not executed until after the output stream is
     * closed. Thus, output from debugging statements in the "write" handler
     * will never be seen in the browser. If debugging output is necessary, it
     * is suggested that the debug output be written to a file instead.
     *
     * @param string $id The session identifier
     * @param mixed $sessionData The session data
     * @return mixed
     */
    abstract public function write( $id, $sessionData );

    /**
     * Destroy session with ID
     *
     * @param string $id The session identifier
     * @return mixed
     */
    abstract public function destroy( $id );

    /**
     * Session garbage collection
     *
     * Executed when the PHP session garbage collector is invoked; should
     * remove all session data older than the `$maxLifetime`.
     *
     * @param int $maxLifetime
     * @return mixed
     */
    abstract public function gc( $maxLifetime );

}