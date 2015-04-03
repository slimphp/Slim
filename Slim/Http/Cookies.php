<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Http;

/**
 * Cookie helper
 */
class Cookies
{
    /**
     * Convert array to header value
     *
     * @param array $cookie Cookie properties
     *
     * @return string
     */
    public static function arrayToString(array $cookie)
    {
        if (!isset($cookie['value'])) {
            throw new \InvalidArgumentException('Cookie properties array must have a `value` key');
        }
        $value = urlencode((string)$cookie['value']);

        if (isset($cookie['domain'])) {
            $value .= '; domain=' . $cookie['domain'];
        }

        if (isset($cookie['path'])) {
            $value .= '; path=' . $cookie['path'];
        }

        if (isset($cookie['expires'])) {
            if (is_string($cookie['expires'])) {
                $timestamp = strtotime($cookie['expires']);
            } else {
                $timestamp = (int)$cookie['expires'];
            }
            if ($timestamp !== 0) {
                $value .= '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }

        if (isset($cookie['secure']) && $cookie['secure']) {
            $value .= '; secure';
        }

        if (isset($cookie['httponly']) && $cookie['httponly']) {
            $value .= '; HttpOnly';
        }

        return $value;
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
