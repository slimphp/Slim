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

/**
 * Request Interface
 *
 * @package Slim
 * @author  John Porter
 * @since   3.0.0
 */
interface RequestInterface
{
    /***** Header *****/

    public function getProtocolVersion();

    public function getMethod();

    public function setMethod($method);

    public function getUrl();

    public function setUrl($url);

    public function getHeaders();

    public function hasHeader($name);

    public function getHeader($name);

    public function setHeader($name, $value);

    public function setHeaders(array $headers);

    public function addHeader($name, $value);

    public function addHeaders(array $headers);

    public function removeHeader($name);

    /***** Body *****/

    public function getBody();

    public function setBody(\GuzzleHttp\Stream\StreamInterface $body);

    /***** Metadata *****/

    public function getScriptName();

    public function getPathInfo();

    public function getPath();

    public function getQueryString();

    public function isGet();

    public function get($key = null, $default = null);

    public function isPost();

    public function post($key = null, $default = null);

    public function isPut();

    public function put($key = null, $default = null);

    public function isPatch();

    public function patch($key = null, $default = null);

    public function isDelete();

    public function delete($key = null, $default = null);

    public function isHead();

    public function isOptions();

    public function isAjax();

    public function isXhr();

    public function isFormData();
}
