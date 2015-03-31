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
namespace Slim\Interfaces\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Slim Request Interface
 *
 * @package Slim
 * @author  Jeremy Kendall
 * @since   3.0.0
 */
interface RequestInterface extends ServerRequestInterface
{
    /**
     * Get the original HTTP method (ignore override)
     *
     * @return string
     */
    public function getOriginalMethod();

    /**
     * Does this request use a given method?
     *
     * @param  string $method HTTP method
     * @return bool
     */
    public function isMethod($method);

    /**
     * Is this a GET request?
     *
     * @return bool
     */
    public function isGet();

    /**
     * Is this a POST request?
     *
     * @return bool
     */
    public function isPost();

    /**
     * Is this a PUT request?
     *
     * @return bool
     */
    public function isPut();

    /**
     * Is this a PATCH request?
     *
     * @return bool
     */
    public function isPatch();

    /**
     * Is this a DELETE request?
     *
     * @return bool
     */
    public function isDelete();

    /**
     * Is this a HEAD request?
     *
     * @return bool
     */
    public function isHead();

    /**
     * Is this a OPTIONS request?
     *
     * @return bool
     */
    public function isOptions();

    /**
     * Is this an AJAX request?
     *
     * @return bool
     */
    public function isAjax();

    /**
     * Is this an XHR request?
     *
     * @see    isAjax()
     * @return bool
     */
    public function isXhr();

    /**
     * Get request content type
     *
     * @return string|null The request content type, if known
     */
    public function getContentType();

    /**
     * Get request media type, if known
     *
     * @return string|null The request media type, minus content-type params
     */
    public function getMediaType();

    /**
     * Get request media type params, if known
     *
     * @return array
     */
    public function getMediaTypeParams();

    /**
     * Get request content character set, if known
     *
     * @return string|null
     */
    public function getContentCharset();

    /**
     * Get request content length, if known
     *
     * @return int|null
     */
    public function getContentLength();

    /**
     * Register media type parser
     *
     * @param string   $mediaType A HTTP media type (excluding content-type params)
     * @param callable $callable  A callable that returns parsed contents for media type
     */
    public function registerMediaTypeParser($mediaType, callable $callable);

    /**
     * Fetch request parameter value from
     * body or query string (in that order).
     *
     * @param  string $key The parameter key
     *
     * @return mixed The parameter value
     */
    public function getParam($key);

    /**
     * Fetch assocative array of body and query string parameters
     *
     * @return array
     */
    public function getParams();
}
