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

class Session extends \Slim\Collection implements \Slim\Interfaces\SessionInterface
{
    /**
     * The session save handler
     * @var mixed
     */
    protected $handler;

    /**
     * Has the session started?
     * @var bool
     */
    protected $started = false;

    /**
     * Has the session finished?
     * @var bool
     */
    protected $finished = false;

    /**
     * Constructor
     * @param  array $options Session settings
     * @param  mixed $handler The session save handler
     * @return void
     */
    public function __construct(array $options = array(), $handler = null)
    {
        // Apply session settings
        $this->setOptions($options);

        // Set custom session handler
        $this->setHandler($handler);
    }

    /**
     * Start the session
     * @return void
     * @throws \RuntimeException If unable to start new PHP session
     */
    public function start()
    {
        if ($this->started === true && $this->finished === false) {
            return;
        }

        if (headers_sent() === false) {
            if (isset($_SESSION) === false || session_id() === '') {
                session_cache_limiter(''); // Disable cache headers from being sent by PHP
                ini_set('session.use_cookies', 1);

                if (session_start() === false) {
                    throw new \RuntimeException('Unable to start session');
                }
            }

            // If headers are already sent, this will act like a normal Set and will
            // not interface with the $_SESSION superglobal.
            $this->data = isset($_SESSION['slim.session']) ? $_SESSION['slim.session'] : array();
        }

        $this->started = true;
    }

    /**
     * Save session data and close session
     * @return void
     */
    public function save()
    {
        $_SESSION['slim.session'] = $this->all();
        $this->finished = true;
        session_write_close();
    }

    /**
     * Regenerate session ID
     * @param  bool $destroy Destroy existing session data?
     * @return bool
     */
    public function regenerate($destroy = false)
    {
        return session_regenerate_id($destroy);
    }

    /**
     * Set session options
     * @param  array $options
     * @return void
     */
    protected function setOptions(array $options)
    {
        $theOptions = array_flip(array(
            'cache_limiter',
            'cookie_domain',
            'cookie_httponly',
            'cookie_lifetime',
            'cookie_path',
            'cookie_secure',
            'entropy_file',
            'entropy_length',
            'gc_divisor',
            'gc_maxlifetime',
            'gc_probability',
            'hash_bits_per_character',
            'hash_function',
            'name',
            'referer_check',
            'serialize_handler',
            'use_cookies',
            'use_only_cookies',
            'use_trans_sid',
            'upload_progress.enabled',
            'upload_progress.cleanup',
            'upload_progress.prefix',
            'upload_progress.name',
            'upload_progress.freq',
            'upload_progress.min-freq',
            'url_rewriter.tags',
        ));

        foreach ($options as $key => $value) {
            if (isset($theOptions[$key])) {
                ini_set('session.' . $key, $value);
            }
        }
    }

    protected function setHandler($handler = null)
    {
        if ($handler !== null) {
            $this->handler = $handler;

            return session_set_save_handler(
                array($this->handler, 'open'),
                array($this->handler, 'close'),
                array($this->handler, 'read'),
                array($this->handler, 'write'),
                array($this->handler, 'destroy'),
                array($this->handler, 'gc')
            );
        }
    }
}
