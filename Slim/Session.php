<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
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
 */
class Session extends Collection implements SessionInterface
{
    /**
     * Reference to array or object from which session data is loaded
     * and to which session data is saved.
     *
     * @var array|\ArrayAccess
     */
    protected $dataSource;

    /**
     * Reference to custom session handler
     *
     * @var SessionHandlerInterface
     */
    protected $handler;

    /**
     * Create new session
     *
     * By default, this class assumes the use of the native file system session handler
     * for persisting session data. You can, however, inject a custom session handler
     * with this constructor method.
     *
     * @param null|SessionHandlerInterface $handler
     */
    public function __construct(SessionHandlerInterface $handler = null)
    {
        if ($handler !== null) {
            session_set_save_handler(
                [$handler, 'open'],
                [$handler, 'close'],
                [$handler, 'read'],
                [$handler, 'write'],
                [$handler, 'destroy'],
                [$handler, 'gc']
            );
            $this->handler = $handler;
        }
    }

    /**
     * Start the session
     */
    public function start()
    {
        // Initialize new session if a session is not already started
        if ($this->isStarted() === false) {
            $this->initialize();
        }

        // Set data source from which session data is loaded, to which session data is saved
        if (isset($this->dataSource) === false) {
            $this->dataSource = &$_SESSION;
        }

        // Load existing session data if available
        if (isset($this->dataSource['slim.session']) === true) {
            $this->replace($this->dataSource['slim.session']);
        }
    }

    /**
     * Save session data to data source
     */
    public function save()
    {
        $this->dataSource['slim.session'] = $this->all();
    }

    /**
     * Is session started?
     *
     * @return bool
     * @link   http://us2.php.net/manual/function.session-status.php#113468
     */
    public function isStarted()
    {
        $started = false;
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            $started = session_status() === PHP_SESSION_ACTIVE ? true : false;
        } else {
            $started = session_id() === '' ? false : true;
        }

        return $started;
    }

    /**
     * Initialize new session
     *
     * @throws \RuntimeException If session cannot start
     */
    public function initialize()
    {
        // Disable PHP cache headers
        session_cache_limiter('');

        // Ensure session ID uses valid characters when stored in HTTP cookie
        if (ini_get('session.use_cookies') == true) {
            ini_set('session.hash_bits_per_character', 5);
        }

        // Start session
        if (session_start() === false) {
            throw new \RuntimeException('Cannot start session. Unknown error while invoking `session_start()`.');
        };
    }

    /**
     * Set data source
     *
     * By default, this class will assume session data is loaded from and saved to the
     * `$_SESSION` superglobal array. However, we can swap in an alternative data source,
     * which is especially useful for unit testing. This should be invoked before `start()`.
     *
     * @param \ArrayAccess $dataSource An alternative data source for loading and persisting session data
     */
    public function setDataSource(\ArrayAccess $dataSource)
    {
        $this->dataSource = $dataSource;
    }
}
