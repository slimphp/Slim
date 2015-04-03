<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Http;

use Slim\Interfaces\EnvironmentInterface;
use Slim\Interfaces\Http\HeadersInterface;

/**
 * Headers
 *
 * This class represents a collection of HTTP headers
 * that is used in both the HTTP request and response objects.
 * It also enables header name case-insensitivity when
 * getting or setting a header value.
 *
 * Each HTTP header can have multiple values. This class
 * stores values into an array for each header name. When
 * you request a header value, you receive an array of values
 * for that header.
 */
class Headers extends Collection implements HeadersInterface
{
    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     *
     * @var array
     */
    protected static $special = [
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE',
    ];

    /**
     * Create new headers collection
     *
     * @param array|null $headers Initial header names and values
     */
    public function __construct(array $headers = null)
    {
        if ($headers) {
            $this->replace($headers);
        }
    }

    /**
     * Create new headers collection with data extracted from
     * the application Environment object
     *
     * @param  Environment $environment The Slim application Environment
     * @return self
     */
    public static function createFromEnvironment(Environment $environment)
    {
        $headers = new static();
        foreach ($environment as $key => $value) {
            $key = strtoupper($key);
            if (strpos($key, 'HTTP_') === 0 || in_array($key, static::$special)) {
                if ($key === 'HTTP_CONTENT_TYPE' || $key === 'HTTP_CONTENT_LENGTH') {
                    continue;
                }
                $headers->set($key, $value);
            }
        }

        return $headers;
    }

    /**
     * Set HTTP header value
     *
     * This method sets a header value. It replaces
     * any values that may already exist for the header name.
     *
     * @param string $key   The case-insensitive header name
     * @param string $value The header value
     */
    public function set($key, $value)
    {
        if (is_array($value) === false) {
            $value = [$value];
        }
        parent::set($this->normalizeKey($key), $value);
    }

    /**
     * Get HTTP header value
     *
     * @param  string     $key     The case-insensitive header name
     * @param  null|mixed $default This argument is unused
     * @return string[]            The header values
     */
    public function get($key, $default = null)
    {
        return parent::get($this->normalizeKey($key), []);
    }

    /**
     * Add HTTP header value
     *
     * This method appends a header value. Unlike the set() method,
     * this method _appends_ this new value to any values
     * that already exist for this header name.
     *
     * @param string       $key   The case-insensitive header name
     * @param array|string $value The new header value(s)
     */
    public function add($key, $value)
    {
        $header = $this->get($key, true);
        if (is_array($value)) {
            $header = array_merge($header, $value);
        } else {
            $header[] = $value;
        }
        parent::set($this->normalizeKey($key), $header);
    }

    /**
     * Does this collection have a given header?
     *
     * @param  string $key The case-insensitive header name
     * @return bool
     */
    public function has($key)
    {
        return parent::has($this->normalizeKey($key));
    }

    /**
     * Remove header from collection
     *
     * @param  string $key The case-insensitive header name
     */
    public function remove($key)
    {
        parent::remove($this->normalizeKey($key));
    }

    /**
     * Normalize header name
     *
     * This method transforms header names into a
     * normalized form. This is how we enable case-insensitive
     * header names in the other methods in this class.
     *
     * @param  string $key The case-insensitive header name
     * @return string      Normalized header name
     */
    public function normalizeKey($key)
    {
        $key = strtolower($key);
        $key = str_replace(['-', '_'], ' ', $key);
        $key = preg_replace('#^http #', '', $key);
        $key = ucwords($key);
        $key = str_replace(' ', '-', $key);

        return $key;
    }
}
