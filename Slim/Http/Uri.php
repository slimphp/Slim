<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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

/**
 * Uri
 *
 * Parses base uri and application uri from Request.
 *
 * @package Slim
 * @author  Josh Lockhart <info@joshlockhart.com>
 * @since   Version 1.0
 */
class Slim_Http_Uri {
    
    /**
     * @var string "https" or "http"
     */
    protected static $scheme;
    
    /**
     * @var string
     */
    protected static $baseUri;
    
    /**
     * @var string
     */
    protected static $uri;
    
    /**
     * @var string The URI query string, excluding leading "?"
     */
    protected static $queryString;
    
    /**
     * Get Base URI without trailing slash
     * @param   bool    $reload Force reparse the base URI?
     * @return  string
     */
    public static function getBaseUri( $reload = false ) {
        if ( $reload || is_null(self::$baseUri) ) {
            $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']; //Full Request URI
            $scriptName = $_SERVER['SCRIPT_NAME']; //Script path from docroot
            $baseUri = strpos($requestUri, $scriptName) === 0 ? $scriptName : str_replace('\\', '/', dirname($scriptName));
            self::$baseUri = rtrim($baseUri, '/');
        }
        return self::$baseUri;
    }

    /**
     * Get URI with leading slash
     * @param   bool    $reload     Force reparse the URI?
     * @return  string
     * @throws  RuntimeException    If unable if unable to determine URI
     */
    public static function getUri( $reload = false ) {
        if ( $reload || is_null(self::$uri) ) {
            $uri = '';
            if ( !empty($_SERVER['PATH_INFO']) ) {
                $uri = $_SERVER['PATH_INFO'];
            } else {
                if ( isset($_SERVER['REQUEST_URI']) ) {
                    $uri = parse_url(self::getScheme() . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH);
                } else if ( isset($_SERVER['PHP_SELF']) ) {
                    $uri = $_SERVER['PHP_SELF'];
                } else {
                    throw new RuntimeException('Unable to detect request URI');
                }
            }
            if ( self::getBaseUri() !== '' && strpos($uri, self::getBaseUri()) === 0 ) {
                $uri = substr($uri, strlen(self::getBaseUri()));
            }
            self::$uri = '/' . ltrim($uri, '/');
        }
        return self::$uri;
    }

    /**
     * Get URI Scheme
     * @param   bool    $reload For reparse the URL scheme?
     * @return  string  "https" or "http"
     */
    public static function getScheme( $reload = false ) {
        if ( $reload || is_null(self::$scheme) ) {
            self::$scheme = ( empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ) ? 'http' : 'https';
        }
        return self::$scheme;
    }

    /**
     * Get URI Query String
     * @param   bool    $reload For reparse the URL query string?
     * @return  string
     */
    public static function getQueryString( $reload = false ) {
        if ( $reload || is_null(self::$queryString) ) {
            self::$queryString = $_SERVER['QUERY_STRING'];
        }
        return self::$queryString;
    }

}