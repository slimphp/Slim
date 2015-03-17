<?php
/**
 * Slim - a micro PHP 5 framework.
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 *
 * @link        http://www.slimframework.com
 *
 * @license     http://www.slimframework.com/license
 *
 * @version     2.6.2
 */

namespace Slim\Middleware;

/**
 * Callback.
 *
 * This is middleware for a Slim application that enables
 * the use of callable when call method is triggered.
 * Callbable receives two arguments
 *  - \Slim\Slim $app the slim application
 *  - \Slim\Middleware $next the next middleware
 *
 * @author     Vassilis Kanellopoulos <contact@kanellov.com>
 *
 * @since      2.6.2
 */
class Callback extends \Slim\Middleware
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * Constructor.
     *
     * @param callable $callback
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($callback)
    {
        $this->setCallback($callback);
    }

    /**
     * Call.
     *
     * Calls provided callback
     */
    public function call()
    {
        call_user_func($this->callback, $this->app, $this->next);
    }

    /**
     * Gets the value of callback.
     *
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Sets the value of callback.
     *
     * @param callable $callback the callback
     *
     * @return self
     *
     * @throws \InvalidArgumentException
     */
    protected function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback provided; not callable');
        }

        $this->callback = $callback;

        return $this;
    }
}
