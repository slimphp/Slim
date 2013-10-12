<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.3
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
namespace Slim\Http;

/**
 * Slim HTTP Request
 *
 * This class provides a simple interface around the Slim application environment
 * and raw HTTP request. Use this class to inspect the current HTTP request, including:
 *
 * - The request method
 * - The request headers
 * - The request cookies
 * - The request body
 * - The request parameters (via GET, POST, etc.)
 *
 * This class also contains many other helper methods to inspect the current HTTP request.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class Request implements \Slim\Interfaces\Http\RequestInterface
{
    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_OVERRIDE = '_METHOD';

    /**
     * @var array
     */
    protected static $formDataMediaTypes = array('application/x-www-form-urlencoded');

    /**
     * Application Environment
     * @var \Slim\Environment
     */
    protected $env;

    /**
     * HTTP request headers
     * @var \Slim\Http\Headers
     * @api
     */
    public $headers;

    /**
     * HTTP request cookies
     * @var \Slim\Container
     * @api
     */
    public $cookies;

    /**
     * HTTP request query parameters
     * @var  array
     */
    protected $queryParameters;

    /**
     * HTTP request body parameters (for content-type "application/x-www-form-urlencoded" only)
     * @var array
     */
    protected $body;

    /**
     * Constructor
     * @param \Slim\Environment $env
     * @api
     */
    public function __construct($env, $headers, $cookies)
    {
        $this->env = $env;
        $this->headers = $headers;
        $this->cookies = $cookies;
    }

    /**
     * Get HTTP method
     * @return string
     * @api
     */
    public function getMethod()
    {
        // Get actual request method
        $method = $this->env->get('REQUEST_METHOD');
        $methodOverride = $this->env->get('HTTP_X_HTTP_METHOD_OVERRIDE');

        // Detect method override (by HTTP header or POST parameter)
        if ($methodOverride) {
            $method = strtoupper($methodOverride);
        } else if ($method === 'POST') {
            $customMethod = $this->post('_METHOD');
            if ($customMethod) {
                $method = strtoupper($customMethod);
            }
        }

        return $method;
    }

    /**
     * Get original HTTP method (before method override applied)
     * @return string
     * @api
     */
    public function getOriginalMethod()
    {
        return $this->env->get('REQUEST_METHOD');
    }

    /**
     * Is this a GET request?
     * @return bool
     * @api
     */
    public function isGet()
    {
        return $this->getMethod() === self::METHOD_GET;
    }

    /**
     * Is this a POST request?
     * @return bool
     * @api
     */
    public function isPost()
    {
        return $this->getMethod() === self::METHOD_POST;
    }

    /**
     * Is this a PUT request?
     * @return bool
     * @api
     */
    public function isPut()
    {
        return $this->getMethod() === self::METHOD_PUT;
    }

    /**
     * Is this a PATCH request?
     * @return bool
     * @api
     */
    public function isPatch()
    {
        return $this->getMethod() === self::METHOD_PATCH;
    }

    /**
     * Is this a DELETE request?
     * @return bool
     * @api
     */
    public function isDelete()
    {
        return $this->getMethod() === self::METHOD_DELETE;
    }

    /**
     * Is this a HEAD request?
     * @return bool
     * @api
     */
    public function isHead()
    {
        return $this->getMethod() === self::METHOD_HEAD;
    }

    /**
     * Is this a OPTIONS request?
     * @return bool
     * @api
     */
    public function isOptions()
    {
        return $this->getMethod() === self::METHOD_OPTIONS;
    }

    /**
     * Is this an AJAX request?
     * @return bool
     * @api
     */
    public function isAjax()
    {
        if ($this->params('isajax')) {
            return true;
        } elseif ($this->headers->get('X_REQUESTED_WITH') === 'XMLHttpRequest') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is this an XHR request? (alias of Slim_Http_Request::isAjax)
     * @return bool
     * @api
     */
    public function isXhr()
    {
        return $this->isAjax();
    }

    /**
     * Fetch GET and POST data
     *
     * This method returns a union of GET and POST data as a key-value array, or the value
     * of the array key if requested; if the array key does not exist, NULL is returned.
     *
     * @param  string           $key
     * @return array|mixed|null
     * @api
     */
    public function params($key = null)
    {
	$get = $this->get() ?: array();
	$post = $this->post() ?: array();
        $union = array_merge($get, $post);
        if ($key) {
            return isset($union[$key]) ? $union[$key] : null;
        }

        return $union;
    }

    /**
     * Fetch GET query parameter(s)
     *
     * Use this method to fetch a GET request query parameter. If the requested GET query parameter
     * identified by the argument does not exist, NULL is returned. If the argument is omittec,
     * all GET query parameters are returned as an array.
     *
     * @param  string               $key    The name of the GET query parameter
     * @return array|string|null
     * @api
     */
    public function get($key = null)
    {
        // Parse and cache query parameters from \Slim\Environment
        if (!isset($this->queryParameters)) {
            if (function_exists('mb_parse_str')) {
                mb_parse_str($this->env->get('QUERY_STRING'), $this->queryParameters);
            } else {
                parse_str($this->env->get('QUERY_STRING'), $this->queryParameters);
            }
        }

        // Fetch requested query parameter(s)
        if ($key) {
            if (isset($this->queryParameters[$key])) {
                $returnVal = $this->queryParameters[$key];
            } else {
                $returnVal = null;
            }
        } else {
            $returnVal = $this->queryParameters;
        }

        return $returnVal;
    }

    /**
     * Fetch POST parameter(s)
     *
     * Use this method to fetch a POST body parameter. If the requested POST body parameter
     * identified by the argument does not exist, NULL is returned. If the argument is omitted,
     * all POST body parameters are returned as an array.
     *
     * @param  string               $key    The name of the POST body parameter
     * @return array|string|null
     * @throws \RuntimeException            If the raw POST body is not available in \Slim\Environment
     * @api
     */
    public function post($key = null)
    {
        // Ensure the POST request body is available
        if (is_null($this->env->get('slim.input'))) {
            throw new \RuntimeException('The raw POST body is not available in \Slim\Environment');
        }

        // Parse and cache POST request body
        if (is_null($this->body)) {
            // Default body
            $this->body = $_POST;

            // Parse body ONLY IF the request content-type is "application/x-www-form-urlencoded"
            if ($this->isFormData() && is_string($this->env->get('slim.input'))) {
                if (function_exists('mb_parse_str')) {
                    mb_parse_str($this->env->get('slim.input'), $this->body);
                } else {
                    parse_str($this->env->get('slim.input'), $this->body);
                }
            }
        }

        // Fetch POST parameter(s)
        if ($key) {
            if (isset($this->body[$key])) {
                $returnVal = $this->body[$key];
            } else {
                $returnVal = null;
            }
        } else {
            $returnVal = $this->body;
        }

        return $returnVal;
    }

    /**
     * Fetch PUT data (alias for \Slim\Http\Request::post)
     * @param  string           $key
     * @return array|string|null
     * @api
     */
    public function put($key = null)
    {
        return $this->post($key);
    }

    /**
     * Fetch PATCH data (alias for \Slim\Http\Request::post)
     * @param  string           $key
     * @return array|string|null
     * @api
     */
    public function patch($key = null)
    {
        return $this->post($key);
    }

    /**
     * Fetch DELETE data (alias for \Slim\Http\Request::post)
     * @param  string           $key
     * @return array|string|null
     * @api
     */
    public function delete($key = null)
    {
        return $this->post($key);
    }

    /**
     * Does the Request body contain parsed form data?
     * @return bool
     * @api
     */
    public function isFormData()
    {
        $method = $this->env->get('slim.method_override.original_method', $this->getMethod());

        return ($method === self::METHOD_POST && is_null($this->getContentType())) || in_array($this->getMediaType(), self::$formDataMediaTypes);
    }

    /**
     * Get Body
     * @return string
     * @api
     */
    public function getBody()
    {
        return $this->env->get('slim.input');
    }

    /**
     * Get Content Type
     * @return string|null
     * @api
     */
    public function getContentType()
    {
        return $this->headers->get('CONTENT_TYPE');
    }

    /**
     * Get Media Type (type/subtype within Content Type header)
     * @return string|null
     * @api
     */
    public function getMediaType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * Get Media Type Params
     * @return array
     * @api
     */
    public function getMediaTypeParams()
    {
        $contentType = $this->getContentType();
        $contentTypeParams = array();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            $contentTypePartsLength = count($contentTypeParts);
            for ($i = 1; $i < $contentTypePartsLength; $i++) {
                $paramParts = explode('=', $contentTypeParts[$i]);
                $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
            }
        }

        return $contentTypeParams;
    }

    /**
     * Get Content Charset
     * @return string|null
     * @api
     */
    public function getContentCharset()
    {
        $mediaTypeParams = $this->getMediaTypeParams();
        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }

        return null;
    }

    /**
     * Get Content-Length
     * @return int
     * @api
     */
    public function getContentLength()
    {
        return $this->headers->get('CONTENT_LENGTH', 0);
    }

    /**
     * Get Host
     * @return string
     * @api
     */
    public function getHost()
    {
        $host = $this->env->get('HTTP_HOST');
        if ($host) {
            if (strpos($host, ':') !== false) {
                $hostParts = explode(':', $host);

                return $hostParts[0];
            }

            return $host;
        }

        return $this->env->get('SERVER_NAME');
    }

    /**
     * Get Host with Port
     * @return string
     * @api
     */
    public function getHostWithPort()
    {
        return sprintf('%s:%s', $this->getHost(), $this->getPort());
    }

    /**
     * Get Port
     * @return int
     * @api
     */
    public function getPort()
    {
        return (int)$this->env->get('SERVER_PORT');
    }

    /**
     * Get Scheme (https or http)
     * @return string
     * @api
     */
    public function getScheme()
    {
        return $this->env->get('slim.url_scheme');
    }

    /**
     * Get Script Name (physical path)
     * @return string
     * @api
     */
    public function getScriptName()
    {
        return $this->env->get('SCRIPT_NAME');
    }

    /**
     * Get Path Info (virtual path)
     * @return string
     * @api
     */
    public function getPathInfo()
    {
        return $this->env->get('PATH_INFO');
    }

    /**
     * Get URL (scheme + host [ + port if non-standard ])
     * @return string
     * @api
     */
    public function getUrl()
    {
        $url = $this->getScheme() . '://' . $this->getHost();
        if (($this->getScheme() === 'https' && $this->getPort() !== 443) || ($this->getScheme() === 'http' && $this->getPort() !== 80)) {
            $url .= sprintf(':%s', $this->getPort());
        }

        return $url;
    }

    /**
     * Get Path (physical path + virtual path)
     * @return string
     * @api
     */
    public function getPath()
    {
        return $this->getScriptName() . $this->getPathInfo();
    }

    /**
     * Get query string
     * @return string
     * @api
     */
    public function getQueryString()
    {
        return $this->env->get('QUERY_STRING');
    }

    /**
     * Get protocol
     * @return string
     * @api
     */
    public function getProtocol()
    {
        return $this->env->get('SERVER_PROTOCOL');
    }

    /**
     * Get IP
     * @return string
     * @api
     */
    public function getIp()
    {
        if ($this->env->get('X_FORWARDED_FOR')) {
            return $this->env->get('X_FORWARDED_FOR');
        } elseif ($this->env->get('CLIENT_IP')) {
            return $this->env->get('CLIENT_IP');
        }

        return $this->env->get('REMOTE_ADDR');
    }

    /**
     * Get Referrer
     * @return string|null
     * @api
     */
    public function getReferrer()
    {
        return $this->headers->get('HTTP_REFERER');
    }

    /**
     * Get Referer (for those who can't spell)
     * @return string|null
     * @api
     */
    public function getReferer()
    {
        return $this->getReferrer();
    }

    /**
     * Get User Agent
     * @return string|null
     * @api
     */
    public function getUserAgent()
    {
        return $this->headers->get('HTTP_USER_AGENT');
    }

    /**
     * Convert HTTP request into a string
     * @return string
     */
    public function __toString()
    {
        // Build path with query string
        $path = $this->getPath();
        $qs = $this->getQueryString();
        if ($qs) {
            $path = sprintf('%s?%s', $path, $qs);
        }

        // Build headers
        $output = sprintf('%s %s %s', $this->getMethod(), $path, $this->getProtocol()) . PHP_EOL;
        foreach ($this->headers as $name => $value) {
            $output .= sprintf("%s: %s", $name, $value) . PHP_EOL;
        }

        // Build body
        $body = $this->getBody();
        if ($body) {
            $output .= PHP_EOL . $body;
        }

        return $output;
    }
}
