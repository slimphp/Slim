<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use \Slim\Interfaces\FlashInterface;
use \Slim\Interfaces\SessionInterface;

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
 */
class Flash implements FlashInterface
{
    /**
     * The flash session storage key
     *
     * @var string
     */
    protected $key;

    /**
     * The session object
     *
     * @var \Slim\Session
     */
    protected $session;

    /**
     * Flash messages
     *
     * @var array
     */
    protected $messages;

    /**
     * Create new Flash object
     *
     * @param \Slim\Session $session
     * @param string        $key     The flash session storage key
     */
    public function __construct(SessionInterface $session, $key = 'slimflash')
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
     *
     * @param string $key   The flash message key
     * @param mixed  $value The flash message value
     */
    public function next($key, $value)
    {
        $this->messages['next'][(string)$key] = $value;
    }

    /**
     * Set flash message for current request
     *
     * @param string $key   The flash message key
     * @param mixed  $value The flash message value
     */
    public function now($key, $value)
    {
        $this->messages['now'][(string)$key] = $value;
    }

    /**
     * Persist flash messages from previous request to the next request
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
     *
     * @return array
     */
    public function getMessages()
    {
        return array_merge($this->messages['prev'], $this->messages['now']);
    }

    /********************************************************************************
     * ArrayAccess interface
     *******************************************************************************/

    /**
     * Offset exists
     *
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
     *
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
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->now($offset, $value);
    }

    /**
     * Offset unset
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->messages['prev'][$offset], $this->messages['now'][$offset]);
    }

    /********************************************************************************
     * IteratorAggregate interface
     *******************************************************************************/

    /**
     * Get iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getMessages());
    }

    /********************************************************************************
     * Countable interface
     *******************************************************************************/

    /**
     * Count
     *
     * @return int
     */
    public function count()
    {
        return count($this->getMessages());
    }
}
