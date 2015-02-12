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
use \Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\StreamableInterface;

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
    protected $protocolVersion = '1.1';

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
     * Response body
     * @var \Psr\Http\Message\StreamableInterface
     */
    protected $body;

    /**
     * Response codes and associated messages
     * @var array
     */
    protected static $messages = array(
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    );

    /**
     * Constructor
     *
     * @param int                                    $status  The response status code
     * @param \Slim\Interfaces\Http\HeadersInterface $headers The response headers
     * @param \Slim\Interfaces\Http\CookiesInterface $cookies The response cookies
     * @param \Psr\Http\Message\StreamableInterface  $body    The response body
     * @api
     */
    public function __construct($status = 200, HeadersInterface $headers = null, CookiesInterface $cookies = null, StreamableInterface $body = null)
    {
        if (is_null($headers) === true) {
            $headers = new Headers();
        }
        if (is_null($cookies) === true) {
            $cookies = new Cookies();
        }
        if (is_null($body) === true) {
            $body = new Body(fopen('php://temp', 'r+'));
        }

        $this->status = (int)$status;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->body = $body;
    }

    /**
     * Clone
     *
     * This method is applied to the cloned object
     * after PHP performs an initial shallow-copy. This
     * method completes a deep-copy by creating new objects
     * for the cloned object's internal reference pointers.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->cookies = clone $this->cookies;
        $this->body = clone $this->body;
    }

    /**
     * Disable magic setter to ensure immutability
     */
    public function __set($name, $value)
    {
        // Do nothing
    }

    /*******************************************************************************
     * Protocol
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
     * Create a new instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version)
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /*******************************************************************************
     * Status
     ******************************************************************************/

    /**
     * Gets the response Status-Code.
     *
     * The Status-Code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * Create a new instance with the specified status code, and optionally
     * reason phrase, for the response.
     *
     * If no Reason-Phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * Status-Code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param integer $code The 3-digit integer result code to set.
     * @param null|string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = null)
    {
        if (isset(static::$messages[$code]) === false) {
            throw new \InvalidArgumentException('Invalid HTTP status code');
        }

        $clone = clone $this;
        $clone->status = (int)$code;
        // NOTE: We ignore custom reason phrases for now. Why? Because.

        return $clone;
    }

    /**
     * Gets the response Reason-Phrase, a short textual description of the Status-Code.
     *
     * Because a Reason-Phrase is not a required element in a response
     * Status-Line, the Reason-Phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * Status-Code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string|null Reason phrase, or null if unknown.
     */
    public function getReasonPhrase()
    {
        return isset(static::$messages[$this->status]) ? static::$messages[$this->status] : null;
    }

    /*******************************************************************************
     * Headers
     ******************************************************************************/

    /**
     * Retrieves all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *               key MUST be a header name, and each value MUST be an array of strings.
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param  string $header Case-insensitive header name.
     * @return bool   Returns true if any header names match the given header
     *                name using a case-insensitive string comparison. Returns false if
     *                no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    /**
     * Retrieve a header by the given case-insensitive name, as a string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeaderLines() instead
     * and supply your own delimiter when concatenating.
     *
     * @param  string $header Case-insensitive header name.
     * @return string
     */
    public function getHeader($name)
    {
        return implode(',', $this->headers->get($name));
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $header Case-insensitive header name.
     * @return string[]
     */
    public function getHeaderLines($name)
    {
        return $this->headers->get($name);
    }

    /**
     * Create a new instance with the provided header, replacing any existing
     * values of any headers with the same case-insensitive name.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new and/or updated header and value.
     *
     * @param string $header Header name
     * @param string|string[] $value Header value(s).
     * @return self
     */
    public function withHeader($header, $value)
    {
        $clone = clone $this;
        $clone->headers->set($header, $value);

        return $clone;
    }

    /**
     * Creates a new instance, with the specified header appended with the
     * given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new header and/or value.
     *
     * @param string $header Header name to add
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($header, $value)
    {
        $clone = clone $this;
        $clone->headers->add($header, $value);

        return $clone;
    }

    /**
     * Creates a new instance, without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the named header.
     *
     * @param string $header HTTP header to remove
     * @return self
     */
    public function withoutHeader($header)
    {
        $clone = clone $this;
        $clone->headers->remove($header);

        return $clone;
    }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    public function getBody()
    {

    }

    public function withBody(StreamableInterface $body)
    {

    }

    /**
     * Gets the body of the message.
     *
     * @return StreamableInterface Returns the body as a stream.
     */
    // public function getBody()
    // {
    //     return $this->body;
    // }

    /**
     * Create a new instance, with the specified message body.
     *
     * The body MUST be a StreamableInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamableInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    // public function withBody(StreamableInterface $body)
    // {
    //     $headers = new Headers($this->getHeaders());
    //     $cookies = new Cookies($this->getCookies());
    //     $status = $this->getStatus();

    //     return new self($headers, $cookies, $body, $status);
    // }

    /*******************************************************************************
     * Cookies
     ******************************************************************************/

    /**
     * Get cookies
     *
     * @return array
     * @api
     */
    // public function getCookies()
    // {
    //     return $this->cookies->all();
    // }

    /**
     * Set multiple cookies
     *
     * @param array $cookies
     * @api
     */
    // public function setCookies(array $cookies)
    // {
    //     $this->cookies->replace($cookies);
    // }

    /**
     * Does this request have a given cookie?
     *
     * @param  string $name
     * @return bool
     * @api
     */
    // public function hasCookie($name)
    // {
    //     return $this->cookies->has($name);
    // }

    /**
     * Get cookie value
     *
     * @param  string $name
     * @return array
     * @api
     */
    // public function getCookie($name)
    // {
    //     return $this->cookies->get($name);
    // }

    /**
     * Set cookie
     *
     * @param string       $name
     * @param array|string $value
     * @api
     */
    // public function setCookie($name, $value)
    // {
    //     $this->cookies->set($name, $value);
    // }

    /**
     * Remove cookie
     *
     * @param string $name
     * @param array  $settings
     * @api
     */
    // public function removeCookie($name, $settings = array())
    // {
    //     $this->cookies->remove($name, $settings);
    // }

    /**
     * Encrypt cookies
     *
     * @param \Slim\Interfaces\CryptInterface $crypt
     * @api
     */
    // public function encryptCookies(\Slim\Interfaces\CryptInterface $crypt)
    // {
    //     $this->cookies->encrypt($crypt);
    // }

    /**
     * Write data to the response body
     *
     * Proxies to the underlying stream and writes the provided data to it.
     *
     * @param string $data
     */
    // public function write($data)
    // {
    //     $this->getBody()->write($data);

    //     return $this;
    // }

    /**
     * Prepare download
     *
     * This method streams a resource to the HTTP client
     *
     * @param  resource $path        A PHP resource
     * @param  string   $name        The download name
     * @param  string   $contentType The download content type
     * @return self
     * @api
     */
    // public function withDownload($resource, $name = false, $contentType = false) {
    //     if (is_resource($resource) === false) {
    //         throw new \InvalidArgumentException('\Slim\Http\Response::withDownload() argument must be a valid PHP resource');
    //     }

    //     // Prepare new response
    //     $headers = new Headers($this->getHeaders());
    //     $cookies = new Cookies($this->getCookies());
    //     $body = new Body($resource);
    //     $status = $this->getStatus();

    //     // Prompt download
    //     $headers->set('Content-Disposition', sprintf(
    //         'attachment;filename=%s',
    //         $name ? $name : basename($path)
    //     ));

    //     // Set content type and length
    //     $contentLength = false;
    //     if (!$contentType) {
    //         $contentType = 'application/octet-stream';
    //         if (file_exists($path)) {
    //             if (extension_loaded('fileinfo')) {
    //                 $finfo = new \finfo(FILEINFO_MIME_TYPE);
    //                 $contentType = $finfo->file($path);
    //             }
    //             $stat = fstat($fp);
    //             $contentLength = $stat['size'];
    //         } else {
    //             $data = stream_get_meta_data($stream);
    //             foreach ($data['wrapper_data'] as $header) {
    //                 list($k, $v) = explode(': ', $header, 2);
    //                 if ($k === 'Content-Type') {
    //                     $contentType = $v;
    //                 } else if ($k === 'Content-Length') {
    //                     $contentLength = $v;
    //                 }
    //             }
    //         }
    //     }
    //     $headers->set('Content-Type', $contentType);
    //     if ($contentLength) {
    //         $headers->set('Content-Length', $contentLength);
    //     }

    //     return new self($headers, $cookies, $body, $code);
    // }

    /*******************************************************************************
     * HTTP caching
     ******************************************************************************/

    // protected $onLastModified;
    // protected $onEtag;

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
    // public function expires($time)
    // {
    //     if (is_string($time) === true) {
    //         $time = strtotime($time);
    //     }
    //     $this->setHeader('Expires', gmdate('D, d M Y H:i:s T', $time));
    // }

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
    // public function onLastModified(callable $callback)
    // {
    //     $this->onLastModified = $callback;
    // }

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
    // public function lastModified($time, callable $callback = null)
    // {
    //     // Convert time to integer value
    //     if (is_integer($time) === false) {
    //         $time = strtotime((string)$time);
    //     }

    //     // Set header
    //     $this->setHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $time));

    //     // Invoke callbacks if necessary
    //     if ($callback) {
    //         $callback($this, $time);
    //     } else if ($this->onLastModified) {
    //         call_user_func_array($this->onLastModified, [$this, $time]);
    //     }
    // }

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
    // public function onEtag(callable $callback)
    // {
    //     $this->onEtag = $callback;
    // }

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
    // public function etag($value, $type = 'strong', callable $callback = null)
    // {
    //     // Ensure type is correct
    //     if (!in_array($type, array('strong', 'weak'))) {
    //         throw new \InvalidArgumentException('Invalid etag type. Must be "strong" or "weak".');
    //     }

    //     // Set etag value
    //     $value = '"' . $value . '"';
    //     if ($type === 'weak') {
    //         $value = 'W/'.$value;
    //     }
    //     $this->setHeader('ETag', $value);

    //     // Invoke callbacks
    //     if ($callback) {
    //         $callback($this, $value);
    //     } else if ($this->onEtag) {
    //         call_user_func_array($this->onEtag, [$this, $value]);
    //     }
    // }

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
    // public function finalize(\Slim\Interfaces\Http\RequestInterface $request)
    // {
    //     $sendBody = true;

    //     if (in_array($this->status, array(204, 304)) === true) {
    //         $this->headers->remove('Content-Type');
    //         $this->headers->remove('Content-Length');
    //         $sendBody = false;
    //     } else {
    //         $size = @$this->getSize();
    //         if ($size) {
    //             $this->headers->set('Content-Length', $size);
    //         }
    //     }

    //     // Serialize cookies into HTTP header
    //     $this->cookies->setHeaders($this->headers);

    //     // Remove body if HEAD request
    //     if ($request->isHead() === true) {
    //         $sendBody = false;
    //     }

    //     // Truncate body if it should not be sent with response
    //     if ($sendBody === false) {
    //         $this->write('', true);
    //     }

    //     return $this;
    // }

    /**
     * Send HTTP response headers and body
     *
     * @return \Slim\Interfaces\Http\Response Self
     * @api
     */
    // public function send()
    // {
    //     // Send headers
    //     if (headers_sent() === false) {
    //         if (strpos(PHP_SAPI, 'cgi') === 0) {
    //             header(sprintf('Status: %s', $this->getReasonPhrase()));
    //         } else {
    //             header(sprintf('%s %s', $this->getProtocolVersion(), $this->getReasonPhrase()));
    //         }

    //         foreach ($this->headers as $name => $value) {
    //             foreach ($value as $hVal) {
    //                 header("$name: $hVal", false);
    //             }
    //         }
    //     }

    //     // Send body
    //     $meta = stream_get_meta_data($this->body);
    //     if ($meta['seekable'] === true) {
    //         fseek($this->body, 0);
    //     }
    //     while (feof($this->body) === false) {
    //         echo fread($this->body, 1024);
    //     }

    //     return $this;
    // }

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
    // public function redirect($url, $status = 302)
    // {
    //     $this->setStatus($status);
    //     $this->headers->set('Location', $url);
    // }

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
