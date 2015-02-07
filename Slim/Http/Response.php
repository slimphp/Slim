<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
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

use \Slim\Interfaces\Http\HeadersInterface;
use \Slim\Interfaces\Http\CookiesInterface;
use \Slim\Interfaces\Http\ResponseInterface;

/**
 * Response
 *
 * This class provides a simple interface around the HTTP response. Use this class
 * to build and inspect the current HTTP response before it is returned to the client:
 *
 * - The response status
 * - The response headers
 * - The response cookies
 * - The response body
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class Response implements ResponseInterface
{
    /**
     * Response protocol version
     * @var string
     */
    protected $protocolVersion = 'HTTP/1.1';

    /**
     * Response status code
     * @var int
     */
    protected $status = 200;

    /**
     * Response headers
     * @var \Slim\Interfaces\Http\HeadersInterface
     */
    protected $headers;

    /**
     * Response cookies
     * @var \Slim\Interfaces\Http\CookiesInterface
     */
    protected $cookies;

    /**
     * Response body (readable, writable, seekable stream)
     * @var resource
     */
    protected $body;

    /**
     * Response body length (`false` if unknown)
     * @var int|false
     */
    protected $length = false;

    /**
     * Response codes and associated messages
     * @var array
     */
    protected static $messages = array(
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        102 => '102 Processing',
        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        207 => '207 Multi-Status',
        208 => '208 Already Reported',
        226 => '226 IM Used',
        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        308 => '308 Permanent Redirect',
        //Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        424 => '424 Failed Dependency',
        426 => '426 Upgrade Required',
        428 => '428 Precondition Required',
        429 => '429 Too Many Requests',
        431 => '431 Request Header Fields Too Large',
        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported',
        506 => '506 Variant Also Negotiates',
        507 => '507 Insufficient Storage',
        508 => '508 Loop Detected',
        510 => '510 Not Extended',
        511 => '511 Network Authentication Required'
    );

    /**
     * Constructor
     *
     * @param \Slim\Interfaces\Http\HeadersInterface $headers The HTTP response headers
     * @param \Slim\Interfaces\Http\CookiesInterface $cookies The HTTP response cookies
     * @param string                                 $body    The HTTP response body
     * @param int                                    $status  The HTTP response status
     * @api
     */
    public function __construct(HeadersInterface $headers, CookiesInterface $cookies, $body = '', $status = 200)
    {
        $this->headers = $headers;
        if ($this->headers->has('Content-Type') === false) {
            $this->headers->set('Content-Type', 'text/html');
        }
        $this->cookies = $cookies;
        $this->setStatus($status);
        $this->body = fopen('php://temp', 'r+');
        $this->write($body);
    }

    /*******************************************************************************
     * Response Header
     ******************************************************************************/

    /**
     * Get HTTP protocol version
     *
     * @return string
     * @api
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Set HTTP protocol version
     *
     * @param string $version Either "HTTP/1.1" or "HTTP/1.0"
     * @api
     */
    public function setProtocolVersion($version)
    {
        $this->protocolVersion = $version;
    }

    /**
     * Get response status code
     *
     * @return int
     * @api
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set response status code
     *
     * @param int $status
     * @api
     */
    public function setStatus($status)
    {
        $this->status = (int)$status;
    }

    /**
     * Get response reason phrase
     *
     * @return string
     * @api
     */
    public function getReasonPhrase()
    {
        if (isset(static::$messages[$this->status]) === true) {
            return static::$messages[$this->status];
        }

        return null;
    }

    /**
     * Get HTTP headers
     *
     * @return array
     * @api
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * Does this request have a given header?
     *
     * @param  string $name
     * @return bool
     * @api
     */
    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    /**
     * Get header value
     *
     * @param  string $name
     * @return string
     * @api
     */
    public function getHeader($name)
    {
        return $this->headers->get($name);
    }

    /**
     * Set header value
     *
     * @param string $name
     * @param string $value
     * @api
     */
    public function setHeader($name, $value)
    {
        $this->headers->set($name, $value);
    }

    /**
     * Set multiple header values
     *
     * @param array $headers
     * @api
     */
    public function setHeaders(array $headers)
    {
        $this->headers->replace($headers);
    }

    /**
     * Add a header value
     *
     * @param string $name
     * @param string $value
     * @api
     */
    public function addHeader($name, $value)
    {
        $this->headers->add($name, $value);
    }

    /**
     * Add multiple header values
     *
     * @param array $headers
     * @api
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->headers->add($name, $value);
        }
    }

    /**
     * Remove header
     *
     * @param string $name
     * @api
     */
    public function removeHeader($name)
    {
        $this->headers->remove($name);
    }

    /**
     * Get cookies
     *
     * @return array
     * @api
     */
    public function getCookies()
    {
        return $this->cookies->all();
    }

    /**
     * Set multiple cookies
     *
     * @param array $cookies
     * @api
     */
    public function setCookies(array $cookies)
    {
        $this->cookies->replace($cookies);
    }

    /**
     * Does this request have a given cookie?
     *
     * @param  string $name
     * @return bool
     * @api
     */
    public function hasCookie($name)
    {
        return $this->cookies->has($name);
    }

    /**
     * Get cookie value
     *
     * @param  string $name
     * @return array
     * @api
     */
    public function getCookie($name)
    {
        return $this->cookies->get($name);
    }

    /**
     * Set cookie
     *
     * @param string       $name
     * @param array|string $value
     * @api
     */
    public function setCookie($name, $value)
    {
        $this->cookies->set($name, $value);
    }

    /**
     * Remove cookie
     *
     * @param string $name
     * @param array  $settings
     * @api
     */
    public function removeCookie($name, $settings = array())
    {
        $this->cookies->remove($name, $settings);
    }

    /**
     * Encrypt cookies
     *
     * @param \Slim\Interfaces\CryptInterface $crypt
     * @api
     */
    public function encryptCookies(\Slim\Interfaces\CryptInterface $crypt)
    {
        $this->cookies->encrypt($crypt);
    }

    /*******************************************************************************
     * Response Body
     ******************************************************************************/

    /**
     * Get response body
     *
     * @return resource
     * @api
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set response body (must be readable, writable, seekable )
     *
     * @param resource $body
     * @api
     */
    public function setBody($body)
    {
        // Validate new body
        if (is_resource($body) === false) {
            throw new \InvalidArgumentException('New response body must be a valid stream resource');
        }

        // Close existinb body
        if (is_resource($this->body) === true) {
            fclose($this->body);
        }

        $this->body = $body;
    }

    /**
     * Append response body
     *
     * @param string $body      Content to append to the current HTTP response body
     * @param bool   $overwrite Clear the existing body before writing new content?
     * @api
     */
    public function write($body, $overwrite = false)
    {
        if ($overwrite === true) {
            fclose($this->body);
            $this->body = fopen('php://temp', 'r+');
            $this->length = 0;
        }
        fwrite($this->body, $body);
        $this->length += function_exists('mb_strlen') ? mb_strlen($body) : strlen($body);
    }

    /**
     * Write JSON data to response body
     *
     * @param array|object|string $data Array, JSON string, or object that implements public `toJson()` or `asJson()` method
     * @throws \InvalidArgumentException
     */
    public function writeJson($data)
    {
        if (is_array($data) === true) {
            $json = json_encode($data);
        } else if (is_object($data) && method_exists($data, 'toJson') === true) {
            $json = $data->toJson();
        } else if (is_object($data) && method_exists($data, 'asJson') === true) {
            $json = $data->asJson();
        } else if (is_string($data) === true) {
            $json = $data;
        } else {
            throw new \InvalidArgumentException('Argument must be array, JSON string, or object with `toJson()` or `asJson()` method');
        }

        $this->write($json, true);
        $this->setHeader('Content-Type', 'application/json;charset=utf-8');
    }

    /**
     * Write XML data to response body
     *
     * @param array|object|string $data Array, XML string, or object that implements public `toXml()` or `asXml()` method
     * @throws \InvalidArgumentException
     */
    public function writeXml($data)
    {
        if (is_array($data) === true) {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = false;
            $root = $dom->createElement('response');
            $dom->appendChild($root);
            $array2xml = function ($node, $array) use ($dom, &$array2xml) {
                foreach ($array as $key => $value){
                    if (is_array($value) === true) {
                        $n = $dom->createElement($key);
                        $node->appendChild($n);
                        $array2xml($n, $value);
                    } else {
                        $attr = $dom->createAttribute($key);
                        $attr->value = $value;
                        $node->appendChild($attr);
                    }
                }
            };
            $array2xml($root, $data);
            $xml = $dom->saveXML();
        } else if (is_object($data) && method_exists($data, 'toXml') === true) {
            $xml = $data->toXml();
        } else if (is_object($data) && method_exists($data, 'asXml') === true) {
            $xml = $data->asXml();
        } else if (is_string($data) === true) {
            $xml = $data;
            if (strpos($data, '<?xml') !== 0) {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . $xml;
            }
        } else {
            throw new \InvalidArgumentException('Argument must be array, string, or object with `toXml()` or `asXml()` method');
        }

        $this->write($xml, true);
        $this->setHeader('Content-Type', 'application/xml;charset=utf-8');
    }

    /**
     * Get the response body size if known
     *
     * @return int|false
     * @api
     */
    public function getSize()
    {
        return $this->length;
    }

    /**
     * Prepare download
     *
     * This method streams a resource to the HTTP client
     *
     * @param string $path        The PHP resource URI
     * @param string $name        The downloaded file name
     * @param bool   $inline      Use inline download disposition?
     * @param string $contentType The downloaded file content type
     * @api
     */
    public function setDownload($path, $name = false, $inline = false, $contentType = false) {
        // Replace response body
        $fp = fopen($path, 'r');
        $this->setBody($fp);

        // Designate as attachment
        if ($inline === false) {
            $this->setHeader('Content-Disposition', sprintf(
                'attachment;filename=%s',
                $name ? $name : basename($path)
            ));
        }

        // Set content type
        if ($contentType) {
            $this->setHeader('Content-Type', $contentType);
        } else {
            if (file_exists($path)) {
                // Set Content-Type
                if (extension_loaded('fileinfo')) {
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $type = $finfo->file($path);
                    $this->setHeader('Content-Type', $type);
                } else {
                    $this->setHeader('Content-Type', 'application/octet-stream');
                }

                // Set Content-Length
                $stat = fstat($fp);
                $this->setHeader('Content-Length', $stat['size']);
            } else {
                // Set Content-Type and Content-Length
                $data = stream_get_meta_data($fp);
                foreach ($data['wrapper_data'] as $header) {
                    list($k, $v) = explode(': ', $header, 2);

                    if ($k === 'Content-Type') {
                        $this->setHeader('Content-Type', $v);
                    } else if ($k === 'Content-Length') {
                        $this->setHeader('Content-Length', $v);
                    }
                }
            }
        }
    }

    /*******************************************************************************
     * HTTP caching
     ******************************************************************************/

    protected $onLastModified;
    protected $onEtag;

    /**
     * Set expires header
     *
     * The `Expires` header tells the HTTP client the time at which
     * the current resource should be considered stale. At that time the HTTP
     * client will send a conditional GET request to the server; the server
     * may return a 200 OK if the resource has changed, else a 304 Not Modified
     * if the resource has not changed. The `Expires` header should be used in
     * conjunction with the `etag()` or `lastModified()` methods above.
     *
     * @param string|int $time If string, a time to be parsed by `strtotime()`;
     *                         If int, a UNIX timestamp;
     * @api
     */
    public function expires($time)
    {
        if (is_string($time) === true) {
            $time = strtotime($time);
        }
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s T', $time));
    }

    /**
     * Set Last Modified callback
     *
     * This method sets the callback to be invoked when a Last-Modified
     * header is set on this Response object. This is useful to halt the
     * application if the Last Modified time matches the Request object's
     * `If-Modified-Since` header.
     *
     * @param callable $callback
     */
    public function onLastModified(callable $callback)
    {
        $this->onLastModified = $callback;
    }

    /**
     * Set Last Modified header
     *
     * This method sets the Last-Modified header and invokes callbacks if available.
     * The callbacks are responsible for halting the application, if necessary, and
     * returning an appropriate response to the HTTP client.
     *
     * @param int|string $time     The last modification date
     * @param callable   $callback Optional callback to invoke
     * @api
     */
    public function lastModified($time, callable $callback = null)
    {
        // Convert time to integer value
        if (is_integer($time) === false) {
            $time = strtotime((string)$time);
        }

        // Set header
        $this->setHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $time));

        // Invoke callbacks if necessary
        if ($callback) {
            $callback($this, $time);
        } else if ($this->onLastModified) {
            call_user_func_array($this->onLastModified, [$this, $time]);
        }
    }

    /**
     * Set ETag callback
     *
     * This method sets the callback to be invoked
     * when a ETag header is set on this Response object.
     * This is useful to halt the application if the ETag
     * time matches the Request object's `If-None-Match` header.
     *
     * @param callable $callback
     */
    public function onEtag(callable $callback)
    {
        $this->onEtag = $callback;
    }

    /**
     * Set ETag header
     *
     * This method sets the ETag header and invokes callbacks if available.
     * The callbacks are responsible for halting the application, if necessary,
     * and returning an appropriate response to the HTTP client.
     *
     * @param  string                    $value    The etag value
     * @param  string                    $type     The etag type (either "strong" or "weak")
     * @param  callable                  $callable Optional callback invoked when etag set
     * @throws \InvalidArgumentException           If invalid etag type
     * @api
     */
    public function etag($value, $type = 'strong', callable $callback = null)
    {
        // Ensure type is correct
        if (!in_array($type, array('strong', 'weak'))) {
            throw new \InvalidArgumentException('Invalid etag type. Must be "strong" or "weak".');
        }

        // Set etag value
        $value = '"' . $value . '"';
        if ($type === 'weak') {
            $value = 'W/'.$value;
        }
        $this->setHeader('ETag', $value);

        // Invoke callbacks
        if ($callback) {
            $callback($this, $value);
        } else if ($this->onEtag) {
            call_user_func_array($this->onEtag, [$this, $value]);
        }
    }

    /*******************************************************************************
     * Response Helpers
     ******************************************************************************/

    /**
     * Finalize response for delivery to client
     *
     * Apply final preparations to the resposne object
     * so that it is suitable for delivery to the client.
     *
     * @param  \Slim\Interfaces\Http\RequestInterface $request
     * @return \Slim\Interfaces\Http\Response Self
     * @api
     */
    public function finalize(\Slim\Interfaces\Http\RequestInterface $request)
    {
        $sendBody = true;

        if (in_array($this->status, array(204, 304)) === true) {
            $this->headers->remove('Content-Type');
            $this->headers->remove('Content-Length');
            $sendBody = false;
        } else {
            $size = @$this->getSize();
            if ($size) {
                $this->headers->set('Content-Length', $size);
            }
        }

        // Serialize cookies into HTTP header
        $this->cookies->setHeaders($this->headers);

        // Remove body if HEAD request
        if ($request->isHead() === true) {
            $sendBody = false;
        }

        // Truncate body if it should not be sent with response
        if ($sendBody === false) {
            $this->write('', true);
        }

        return $this;
    }

    /**
     * Send HTTP response headers and body
     *
     * @return \Slim\Interfaces\Http\Response Self
     * @api
     */
    public function send()
    {
        // Send headers
        if (headers_sent() === false) {
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', $this->getReasonPhrase()));
            } else {
                header(sprintf('%s %s', $this->getProtocolVersion(), $this->getReasonPhrase()));
            }

            foreach ($this->headers as $name => $value) {
                foreach ($value as $hVal) {
                    header("$name: $hVal", false);
                }
            }
        }

        // Send body
        $meta = stream_get_meta_data($this->body);
        if ($meta['seekable'] === true) {
            fseek($this->body, 0);
        }
        while (feof($this->body) === false) {
            echo fread($this->body, 1024);
        }

        return $this;
    }

    /**
     * Redirect
     *
     * This method prepares the response object to return an HTTP Redirect response
     * to the client.
     *
     * @param string $url    The redirect destination
     * @param int    $status The redirect HTTP status code
     * @api
     */
    public function redirect($url, $status = 302)
    {
        $this->setStatus($status);
        $this->headers->set('Location', $url);
    }

    /**
     * Helpers: Empty?
     *
     * @return bool
     * @api
     */
    public function isEmpty()
    {
        return in_array($this->status, array(201, 204, 304));
    }

    /**
     * Helpers: Informational?
     *
     * @return bool
     * @api
     */
    public function isInformational()
    {
        return $this->status >= 100 && $this->status < 200;
    }

    /**
     * Helpers: OK?
     *
     * @return bool
     * @api
     */
    public function isOk()
    {
        return $this->status === 200;
    }

    /**
     * Helpers: Successful?
     *
     * @return bool
     * @api
     */
    public function isSuccessful()
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Helpers: Redirect?
     *
     * @return bool
     * @api
     */
    public function isRedirect()
    {
        return in_array($this->status, array(301, 302, 303, 307));
    }

    /**
     * Helpers: Redirection?
     *
     * @return bool
     * @api
     */
    public function isRedirection()
    {
        return $this->status >= 300 && $this->status < 400;
    }

    /**
     * Helpers: Forbidden?
     *
     * @return bool
     * @api
     */
    public function isForbidden()
    {
        return $this->status === 403;
    }

    /**
     * Helpers: Not Found?
     *
     * @return bool
     * @api
     */
    public function isNotFound()
    {
        return $this->status === 404;
    }

    /**
     * Helpers: Client error?
     *
     * @return bool
     * @api
     */
    public function isClientError()
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Helpers: Server Error?
     *
     * @return bool
     * @api
     */
    public function isServerError()
    {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * Convert response to string
     *
     * @return string
     * @api
     */
    public function __toString()
    {
        $output = sprintf('%s %s', $this->getProtocolVersion(), $this->getReasonPhrase()) . PHP_EOL;
        foreach ($this->headers as $name => $value) {
            $output .= sprintf('%s: %s', $name, $value) . PHP_EOL;
        }
        $body = (string)$this->getBody();
        if ($body) {
            $output .= PHP_EOL . $body;
        }

        return $output;
    }
}
