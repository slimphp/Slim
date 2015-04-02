<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Http;

use Slim\Interfaces\Http\CookiesInterface;
use Slim\Interfaces\Http\HeadersInterface;

/**
 * Cookie
 *
 * This class represents a collection of HTTP response cookies.
 * It lets you manage each cookie's properties using PHP
 * scalar values. These values are serialized into an HTTP header
 * only at the end of the application lifecycle.
 */
class Cookies extends Collection implements CookiesInterface
{
    /**
     * Default cookie properties
     *
     * @var array
     */
    protected $defaults = [
        'value' => '',
        'domain' => null,
        'path' => null,
        'expires' => null,
        'secure' => false,
        'httponly' => false,
    ];

    /**
     * Create new HTTP cookie collection
     *
     * @param array|null $data     Initial cookie names and values
     * @param array|null $defaults Custom cookie default properties
     */
    public function __construct(array $data = null, array $defaults = null)
    {
        if ($defaults) {
            $this->setDefaults($defaults);
        }

        if ($data) {
            $this->replace($data);
        }
    }

    /**
     * Set default cookie properties
     *
     * @param array $settings Custom default cookie properties
     */
    public function setDefaults(array $settings)
    {
        $this->defaults = array_merge($this->defaults, $settings);
    }

    /**
     * Get default cookie properties
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Set HTTP cookie
     *
     * This method adds a new HTTP response cookie to this
     * collection. The second argument can be a string or
     * an array. If a string, the string becomes the cookie
     * value property and adopts the default values of
     * other cookie properties. If an array, the second argument
     * is merged with the default cookie properties.
     *
     * @param string       $key   The cookie name
     * @param string|array $value The cookie value or properties
     */
    public function set($key, $value)
    {
        if (is_array($value)) {
            $settings = array_replace($this->defaults, $value);
        } else {
            $settings = array_replace($this->defaults, ['value' => $value]);
        }

        parent::set($key, $settings);
    }

    /**
     * Remove HTTP response cookie
     *
     * This method removes a cookie from this cookie collection.
     * Technically speaking, this method _sets_ a cookie with an
     * empty value and a time in the past; this prompts the HTTP
     * client to invalidate and remove the client-side cookie.
     *
     * @param  string $key      The cookie name
     * @param  array  $settings The cookie properties, if necessary
     */
    public function remove($key, $settings = [])
    {
        $settings['value'] = '';
        $settings['expires'] = time() - 86400;
        $this->set($key, array_replace($this->defaults, $settings));
    }

    /**
     * Get an HTTP response cookie as HTTP header string
     *
     * @param  string $key  The cookie name
     * @return string       The equivalent `Cookie:` header value
     */
    public function getAsString($key)
    {
        $output = null;
        $cookie = $this->get($key);
        if ($cookie) {
            $value = (string)$cookie['value'];
            $parts = [];

            if (isset($cookie['domain']) && $cookie['domain']) {
                $parts[] = '; domain=' . $cookie['domain'];
            }

            if (isset($cookie['path']) && $cookie['path']) {
                $parts[] = '; path=' . $cookie['path'];
            }

            if (isset($cookie['expires'])) {
                if (is_string($cookie['expires'])) {
                    $timestamp = strtotime($cookie['expires']);
                } else {
                    $timestamp = (int)$cookie['expires'];
                }

                if ($timestamp !== 0) {
                    $parts[] = '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
                }
            }

            if (isset($cookie['secure']) && $cookie['secure']) {
                $parts[] = '; secure';
            }

            if (isset($cookie['httponly']) && $cookie['httponly']) {
                $parts[] = '; HttpOnly';
            }

            $output = sprintf(
                '%s=%s',
                urlencode($key),
                urlencode($value) . implode('', $parts)
            );
        }

        return $output;
    }

    /**
     * Parse HTTP request `Cookie:` header and extract
     * into a PHP associative array.
     *
     * @param  string $header The raw HTTP request `Cookie:` header
     * @return array          Associative array of cookie names and values
     */
    public static function parseHeader($header)
    {
        if (is_array($header) === true) {
            $header = isset($header[0]) ? $header[0] : '';
        }

        if (is_string($header) === false) {
            throw new \InvalidArgumentException('Cannot parse Cookie data. Header value must be a string.');
        }

        $header = rtrim($header, "\r\n");
        $pieces = preg_split('@\s*[;,]\s*@', $header);
        $cookies = [];

        foreach ($pieces as $cookie) {
            $cookie = explode('=', $cookie, 2);

            if (count($cookie) === 2) {
                $key = urldecode($cookie[0]);
                $value = urldecode($cookie[1]);

                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }
}
