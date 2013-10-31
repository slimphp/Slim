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
 * Middleware
 *
 * This abstract class provides the core scaffolding for Slim application
 * middleware. Middleware is a layer of logic that wraps itself around
 * the core Slim application. Each middleware applied to a Slim application
 * will run before and after the Slim application is run.
 *
 * With middleware, you can access the core Slim application objects,
 * such as the environment, request, response, view, router, etc.,
 * to affect how the Slim application is run and how it utlimately responds to
 * the HTTP client.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
abstract class Middleware
{
    /**
     * Reference to the primary application instance
     * @var \Slim\App
     */
    protected $app;

    /**
     * Reference to the next downstream middleware
     * @var \Slim\Middleware|\Slim\App
     */
    protected $next;

    /**
     * Set application
     *
     * This method injects the primary Slim application instance into
     * this middleware.
     *
     * @param  \Slim\App $application
     */
    final public function setApplication($application)
    {
        $this->app = $application;
    }

    /**
     * Get application
     *
     * This method retrieves the application previously injected
     * into this middleware.
     *
     * @return \Slim\App
     */
    final public function getApplication()
    {
        return $this->app;
    }

    /**
     * Set next middleware
     *
     * This method injects the next downstream middleware into
     * this middleware so that it may optionally be called
     * when appropriate.
     *
     * @param \Slim\App|\Slim\Middleware
     */
    final public function setNextMiddleware($nextMiddleware)
    {
        $this->next = $nextMiddleware;
    }

    /**
     * Get next middleware
     *
     * This method retrieves the next downstream middleware
     * previously injected into this middleware.
     *
     * @return \Slim\App|\Slim\Middleware
     */
    final public function getNextMiddleware()
    {
        return $this->next;
    }

    /**
     * Call
     *
     * Perform actions specific to this middleware and optionally
     * call the next downstream middleware.
     */
    abstract public function call();
}
