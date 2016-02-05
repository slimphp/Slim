<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Http;

use Slim\Collection;
use Slim\Interfaces\Http\EnvironmentInterface;

/**
 * Environment
 *
 * This class decouples the Slim application from the global PHP environment.
 * This is particularly useful for unit testing, but it also lets us create
 * custom sub-requests.
 */
class Environment extends Collection implements EnvironmentInterface
{
    /**
     * List of trusted proxies
     *
     * @var array
     */
    protected $trustedProxies = [];

    /**
     * Create mock environment
     *
     * @param  array $userData Array of custom environment keys and values
     *
     * @return self
     */
    public static function mock(array $userData = [])
    {
        $data = array_merge([
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'SCRIPT_NAME'          => '',
            'REQUEST_URI'          => '',
            'QUERY_STRING'         => '',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'Slim Framework',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REQUEST_TIME'         => time(),
            'REQUEST_TIME_FLOAT'   => microtime(true),
        ], $userData);

        return new static($data);
    }

    /**
     * Sets a list of trusted proxies.
     *
     * @param array $proxies A list of trusted proxies
     */
    public function setTrustedProxies(array $trustedProxies)
    {
        $this->trustedProxies = $trustedProxies;
    }

    /**
     * Checks whether the request is secure or not.
     *
     * This method can read the client protocol from the "X-Forwarded-Proto" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
     *
     * @return bool
     */
    public function isSecure()
    {
        if ($this->has('HTTP_X_FORWARDED_PROTO') && $this->isFromTrustedProxy()) {
            return $this->get('HTTP_X_FORWARDED_PROTO') === 'https';
        }

        if ($this->has('HTTPS')) {
            return !empty($this->get('HTTPS')) && $this->get('HTTPS') !== 'off';
        }

        return false;
    }

    protected function isFromTrustedProxy()
    {
        return $this->trustedProxies && IpUtils::checkIp($this->get('REMOTE_ADDR'), $this->trustedProxies);
    }
}
