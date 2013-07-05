<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.2.0
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
namespace Slim\Middleware;

/**
 * Session Middleware
 *
 * This middleware provides a simple interface to interact with PHP
 * session data. After adding this middleware to your application,
 * a `session` property will become available on the Slim instance.
 *
 * This middleware's constructor accepts two optional arguments:
 *
 * 1. Session settings
 * An array of INI settings used to configure the PHP session. Array
 * keys should omit the "session." prefix. See
 * http://php.net/manual/en/session.configuration.php
 *
 * 2. Session save handler
 * An object that implements the \SessionHandlerInterface interface. See
 * http://php.net/manual/en/class.sessionhandlerinterface.php
 *
 * USAGE:
 *
 * $app->add(new \Slim\Middleware\Session());
 *
 * @package    Slim
 * @author     Josh Lockhart
 * @since      2.3.0
 */
class Session extends \Slim\Middleware
{
    public function call()
    {
        $this->app->session->start();
        $this->next->call();
        $this->app->flash->save();
        $this->app->session->save();
    }
}
