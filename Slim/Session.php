<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.0
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

use \Slim\Collection;
use \Slim\Interfaces\SessionInterface;
use \Slim\Interfaces\SessionHandlerInterface;

/**
 * Session
 *
 * This class provides a wrapper around a native PHP session. It will
 * start a new PHP session on-demand if a PHP session is not already
 * started. If a session is already started, this class will use the existing
 * session; however, it is recommended that you allow this class to start
 * and configure the PHP session on its own.
 *
 * This class will use the default filesystem session storage. You may
 * define your own session handler to persist session data elsewhere.
 *
 * Session data is serialized and placed in the `slim.session` namespace
 * to avoid polluting the global session namespace potentially used
 * by third-party code.
 *
 * @package    Slim
 * @author     Josh Lockhart
 * @since      2.3.0
 */
class Session extends Collection implements SessionInterface
{
    /**
     * Reference to array or object from which session data is loaded
     * and to which session data is saved.
     * @var array|\ArrayAccess
     */
    protected $dataSource;

    /**
     * Reference to custom session handler
     * @var SessionHandlerInterface
     */
    protected $handler;

    /**
     * Constructor
     *
     * By default, this class assumes the use of the native file system session handler
     * for persisting session data. This method allows us to use a custom handler.
     *
     * @param  SessionHandlerInterface $handler A custom session handler
     * @api
     */
    public function __construct(SessionHandlerInterface $handler = null)
    {
        if ($handler !== null) {
            session_set_save_handler(
                array($handler, 'open'),
                array($handler, 'close'),
                array($handler, 'read'),
                array($handler, 'write'),
                array($handler, 'destroy'),
                array($handler, 'gc')
            );
            $this->handler = $handler;
        }
    }

    /**
     * Set data source
     *
     * By default, this class will assume session data is loaded from and saved to the
     * `$_SESSION` superglobal array. However, we can swap in an alternative data source,
     * which is especially useful for unit testing.
     *
     * @param \ArrayAccess $dataSource An alternative data source for loading and persisting session data
     * @return bool
     * @api
     */
    public function setDataSource(\ArrayAccess $dataSource)
    {
        return $this->dataSource = $dataSource;
    }

    /**
     * Start the session
     * @return bool
     * @throws \RuntimeException If `session_start()` fails
     * @api
     */
    public function start()
    {
        $started = (session_id() !== '');

        if ($started === false) {
            // Disable PHP cache headers
            session_cache_limiter(false);

            // Ensure session ID uses valid characters when stored in HTTP cookie
            if ((bool)ini_get('session.use_cookies') === true) {
                ini_set('session.hash_bits_per_character', 5);
            }

            if (session_start() === false) {
                throw new \RuntimeException('Cannot start session. Unknown error while invoking `session_start()`.');
            }
            $started = true;
        }

        // Set data source
        if (isset($this->dataSource) === false) {
            $this->dataSource = &$_SESSION;
        }

        // Load existing session data if available
        if (isset($this->dataSource['slim.session']) === true) {
            $this->replace($this->dataSource['slim.session']);
        }

        return $started;
    }

    /**
     * Save session data
     * @return bool
     * @api
     */
    public function save()
    {
        return $this->dataSource['slim.session'] = $this->all();
    }
}
