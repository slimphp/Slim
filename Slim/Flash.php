<?php
namespace Slim;

class Flash implements \ArrayAccess, \IteratorAggregate
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
     * @param \Slim\Session $session
     * @param string        $key     The flash session storage key
     */
    public function __construct(\Slim\Session $session, $key = 'slimflash')
    {
        $this->session = $session;
        $this->key = $key;
        $this->messages = array(
            'prev' => isset($session[$key]) ? $session[$key] : array(),
            'next' => array(),
            'now' => array()
        );
    }

    /**
     * Set flash message for next request
     * @param  string   $key   The flash message key
     * @param  mixed    $value The flash message value
     */
    public function next($key, $value)
    {
        $this->messages['next'][(string)$key] = $value;
    }

    /**
     * Set flash message for current request
     * @param  string $key   The flash message key
     * @param  mixed  $value The flash message value
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
     * @return array
     */
    public function getMessages()
    {
        return array_merge($this->messages['prev'], $this->messages['now']);
    }

    /********************************************************************************
     * Array access interface
     *******************************************************************************/

    /**
     * Offset exists
     */
    public function offsetExists($offset)
    {
        $messages = $this->getMessages();

        return isset($messages[$offset]);
    }

    /**
     * Offset get
     */
    public function offsetGet($offset)
    {
        $messages = $this->getMessages();

        return isset($messages[$offset]) ? $messages[$offset] : null;
    }

    /**
     * Offset set
     */
    public function offsetSet($offset, $value)
    {
        $this->now($offset, $value);
    }

    /**
     * Offset unset
     */
    public function offsetUnset($offset)
    {
        unset($this->messages['prev'][$offset], $this->messages['now'][$offset]);
    }

    /********************************************************************************
     * Interator Aggregate interface
     *******************************************************************************/

    /**
     * Get iterator
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getMessages());
    }
}
