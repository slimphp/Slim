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
     * Set flash session storage key
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get flash session storage key
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set flash message for next request
     * @param  string   $key   The flash message key
     * @param  mixed    $value The flash message value
     */
    public function next($key, $value)
    {
        // NOTE: This is a makeshift hack. If I instead do this in `save()`:
        //
        // $this->session->set($this->getKey(), $this->messages['next'])
        //
        // it will not work for whatever reason. I will revert to that
        // when I figure out why it isn't working. For now, this will do.
        $values = isset($this->session[$this->getKey()]) ? $this->session[$this->getKey()] : array();
        $values[$key] = $value;
        $this->session->set($this->getKey(), $values);
        // $this->messages['next'][(string)$key] = $value;
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
            // $this->messages['next'][$key] = $val;
        }
    }

    /**
     * Save flash messages to session
     */
    public function save()
    {
        //$this->session->set($this->getKey(), $this->messages['next']);
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
