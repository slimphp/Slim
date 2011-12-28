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
 * Flash Messaging
 *
 * This class enables Flash messaging. Messages are persisted in $_SESSION
 * with a user-defined key.
 *
 * USAGE:
 *
 * 1. Set Flash message to be shown on the next request
 *
 *      Slim::flash('error', 'The object could not be saved');
 *
 * 2. Set Flash message to be shown on the current request
 *
 *      Slim::flashNow('error', 'The object could not be saved');
 *
 * 3. Keep old Flash messages for the next request
 *
 *      Slim::flashKeep();
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   Version 1.3
 */
class Slim_Session_Flash implements ArrayAccess {

    /**
     * @var string Key used to identify flash information in $_SESSION array
     */
    protected $sessionKey = 'flash';

    /**
     * @var array[array] Storage for flash messages
     */
    protected $messages = array(
        'prev' => array(), //flash messages from prev request
        'next' => array(), //flash messages for next request
        'now' => array() //flash messages for current request
    );

    /**
     * Constructor
     *
     * Establishes Flash session key and loads existing
     * Flash messages from the $_SESSION.
     *
     * @param   string $sessionKey
     * @return  void
     */
    public function __construct( $sessionKey = null ) {
        if ( !is_null($sessionKey) ) {
            $this->setSessionKey($sessionKey);
        }
        $this->load();
    }

    /**
     * Set the $_SESSION key used to access Flash messages
     * @param   string $key
     * @throws  RuntimeException If session key is null
     * @return  Slim_Session_Flash
     */
    public function setSessionKey( $key ) {
        if ( is_null($key) ) {
            throw new RuntimeException('Session key cannot be null');
        }
        $this->sessionKey = (string)$key;
        return $this;
    }

    /**
     * Get the $_SESSION key used to access Flash messages
     * @return string
     */
    public function getSessionKey() {
        return $this->sessionKey;
    }

    /**
     * Set a Flash message for the current request
     * @param   string              $key
     * @param   string              $value
     * @return  Slim_Session_Flash
     */
    public function now( $key, $value ) {
        $this->messages['now'][(string)$key] = $value;
        return $this->save();
    }

    /**
     * Set a Flash message for the next request
     * @param   string              $key
     * @param   string              $value
     * @return  Slim_Session_Flash
     */
    public function set( $key, $value ) {
        $this->messages['next'][(string)$key] = $value;
        return $this->save();
    }

    /**
     * Get Flash messages intended for the current request's View
     * @return array[String]
     */
    public function getMessages() {
        return array_merge($this->messages['prev'], $this->messages['now']);
    }

    /**
     * Load Flash messages from $_SESSION
     * @return Slim_Session_Flash
     */
    public function load() {
        $this->messages['prev'] = isset($_SESSION[$this->sessionKey]) ? $_SESSION[$this->sessionKey] : array();
        return $this;
    }

    /**
     * Transfer Flash messages from the previous request
     * so they are available to the next request.
     * @return Slim_Session_Flash
     */
    public function keep() {
        foreach ( $this->messages['prev'] as $key => $val ) {
            $this->messages['next'][$key] = $val;
        }
        return $this->save();
    }

    /**
     * Save Flash messages to $_SESSION
     * @return Slim_Session_Flash
     */
    public function save() {
        $_SESSION[$this->sessionKey] = $this->messages['next'];
        return $this;
    }

    /***** ARRAY ACCESS INTERFACE *****/

    public function offsetExists( $offset ) {
        $messages = $this->getMessages();
        return isset($messages[$offset]);
    }

    public function offsetGet( $offset ) {
        $messages = $this->getMessages();
        return isset($messages[$offset]) ? $messages[$offset] : null;
    }

    public function offsetSet( $offset, $value ) {
        $this->now($offset, $value);
    }

    public function offsetUnset( $offset ) {
        unset($this->messages['prev'][$offset]);
        unset($this->messages['now'][$offset]);
    }

}