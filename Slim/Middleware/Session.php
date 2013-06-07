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
 * Session Cookie
 *
 * This class provides an HTTP cookie storage mechanism
 * for session data. This class avoids using a PHP session
 * and instead serializes/unserializes the $_SESSION global
 * variable to/from an HTTP cookie.
 *
 * If a secret key is provided with this middleware, the HTTP
 * cookie will be checked for integrity to ensure the client-side
 * cookie is not changed.
 *
 * You should NEVER store sensitive data in a client-side cookie
 * in any format, encrypted or not. If you need to store sensitive
 * user information in a session, you should rely on PHP's native
 * session implementation, or use other middleware to store
 * session data in a database or alternative server-side cache.
 *
 * Because this class stores serialized session data in an HTTP cookie,
 * you are inherently limited to 4 Kb. If you attempt to store
 * more than this amount, serialization will fail.
 *
 * @package    Slim
 * @author     Josh Lockhart
 * @since      2.3.0
 */
class Session extends \Slim\Middleware
{
    /**
     * Constructor
     * @param  array $settings The session settings
     * @param  mixed $handler  The custom session handler
     */
    public function __construct(array $settings = array(), $handler = null)
    {
        $this->app->session = function ($c) use ($settings, $handler) {
            return new \Slim\Session($settings['options'], $settings['handler']);
        });
    }

    public function call()
    {
        $this->app->session->start();
        $this->next->call();
        $this->app->session->save();
    }
}
