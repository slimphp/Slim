<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
 * @package     Slim
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
namespace Slim;

/**
 * Flash
 *
 * This class enables temporary messages to persist across HTTP requests. This
 * is useful to display informational or error messages to end-users after
 * a form is submitted and processed (e.g. "Item saved successfully" or
 * "There are errors with your submission").
 *
 * Flash messages may be set to display for the current HTTP request
 * or for the next HTTP request. Flash messages will not persist beyond the
 * subsequent HTTP request unless you explicitly pass them forward with
 * the `keep` method.
 *
 * Flash messages are stored in the PHP session in the `slimflash` namespace.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   3.0.0
 */
class Flash implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * The flash session storage key
     * @var string
     */
    protected $key;

    /**
     * The session object
     * @var \Slim\Session
     */
    protected $session;

    /**
     * The flash messages
     * @var array
     */
    protected $messages;

    /**
     * Constructor
     * @param  \Slim\Session $session
     * @param  string        $key     The flash session storage key
     * @api
     */
    public function __construct(\Slim\Interfaces\SessionInterface $session, $key = 'slimflash')
    {
        $this->session = $session;
        $this->key = $key;
        $this->messages = array(
            'prev' => $session->get($key, array()),
            'next' => array(),
            'now' => array()
        );
    }

    /**
     * Set flash message for next request
     * @param  string $key   The flash message key
     * @param  mixed  $value The flash message value
     * @api
     */
    public function next($key, $value)
    {
        $this->messages['next'][(string)$key] = $value;
    }

    /**
     * Set flash message for current request
     * @param  string $key   The flash message key
     * @param  mixed  $value The flash message value
     * @api
     */
    public function now($key, $value)
    {
        $this->messages['now'][(string)$key] = $value;
    }

    /**
     * Persist flash messages from previous request to the next request
     * @api
     */
    public function keep()
    {
        foreach ($this->messages['prev'] as $key => $val) {
            $this->next($key, $val);
        }
    }

    /**
     * Save flash messages to session
     */
    public function save()
    {
        $this->session->set($this->key, $this->messages['next']);
    }

    /**
     * Return flash messages to be shown for the current request
     * @return array
     * @api
     */
    public function getMessages()
    {
        return array_merge($this->messages['prev'], $this->messages['now']);
    }

    /**
     * Offset exists
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $messages = $this->getMessages();

        return isset($messages[$offset]);
    }

    /**
     * Offset get
     * @param  mixed      $offset
     * @return mixed|null The value at specified offset, or null
     */
    public function offsetGet($offset)
    {
        $messages = $this->getMessages();

        return isset($messages[$offset]) ? $messages[$offset] : null;
    }

    /**
     * Offset set
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->now($offset, $value);
    }

    /**
     * Offset unset
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->messages['prev'][$offset], $this->messages['now'][$offset]);
    }

    /**
     * Get iterator
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getMessages());
    }

    /**
     * Count
     * @return int
     */
    public function count()
    {
        return count($this->getMessages());
    }
}
