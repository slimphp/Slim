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
 * Session Cookie Handler
 *
 * This class is used as an adapter for PHP's $_SESSION handling.
 * Session data will be written to and read from signed, encrypted
 * cookies. If the current PHP installation does not have the `mcrypt`
 * extension, session data will be written to signed but unencrypted
 * cookies; however, the session cookies will still be secure and will
 * become invalid if manually edited after set by PHP.
 *
 * @package Slim
 * @author Josh Lockhart
 * @since Version 1.3
 */
class Slim_Session_Handler_Cookies extends Slim_Session_Handler {

    public function open( $savePath, $sessionName ) {
        return true;
    }

    public function close() {
        return true; //Not used
    }

    public function read( $id ) {
        return $this->app->getEncryptedCookie($id);
    }

    public function write( $id, $sessionData ) {
        $this->app->setEncryptedCookie($id, $sessionData, 0);
    }

    public function destroy( $id ) {
        $this->app->deleteCookie($id);
    }

    public function gc( $maxLifetime ) {
        return true; //Not used
    }

}