<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.4
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
class Response
{
    /**
     * Response status code
     * @var int
     */
    protected $status;

    /**
     * Response headers
     * @var \Slim\Http\Headers
     * @api
     */
    public $headers;

    /**
     * Response cookies
     * @var \Slim\Http\Cookies
     * @api
     */
    public $cookies;

    /**
     * Response body
     * @var string|resource
     */
    protected $body;

    /**
     * Is this response a resource stream?
     * @var bool
     */
    protected $isStream;

    /**
     * Response body length
     * @var int
     */
    protected $length;

    /**
     * Response codes and associated messages
     * @var array
     */
    protected static $messages = array(
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
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
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported'
    );

    /**
     * Constructor
     * @param string                   $body    The HTTP response body
     * @param int                      $status  The HTTP response status
     * @param \Slim\Http\Headers|array $headers The HTTP response headers
     * @api
     */
    public function __construct($body = '', $status = 200, $headers = array())
    {
        $this->setStatus($status);
        $this->headers = new \Slim\Http\Headers(array('Content-Type' => 'text/html'));
        $this->headers->replace($headers);
        $this->cookies = new \Slim\Http\Cookies();
        $this->isStream = false;
        $this->write($body, true);
    }

    /**
     * Get response status code
     * @return int
     * @api
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set response status code
     * @param int $status The HTTP status code
     * @api
     */
    public function setStatus($status)
    {
        $this->status = (int)$status;
    }

    /**
     * Get response body
     * @return string|resource
     * @api
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set response body
     * @param string $content The new response body
     * @api
     */
    public function setBody($content)
    {
        $this->write($content, true);
    }

    /**
     * Is the response body a resource stream?
     * @return bool
     * @api
     */
    public function isStream()
    {
        return $this->isStream;
    }

    /**
     * Append response body
     * @param  string   $body       Content to append to the current HTTP response body
     * @param  bool     $replace    Overwrite existing response body?
     * @return string               The updated HTTP response body
     * @api
     */
    public function write($body, $replace = false)
    {
        if ($replace) {
            $this->body = $body;
        } else {
            $this->body .= (string)$body;
        }
        $this->length = strlen($this->body);

        return $this->body;
    }

   /**
    * Set the response body to a stream resource
    * @param resource $handle Resource stream to send
    * @api
    */
    public function stream($handle)
    {
        $this->isStream = true;
        $this->body = $handle;
    }

    /**
     * Get the response body stream resource
     * @return resource|false
     * @api
     */
    public function getStream()
    {
        return ($this->isStream) ? $this->body : false;
    }

    /**
     * Get the response body length
     * @return int
     * @api
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Finalize response for delivery to client
     *
     * Apply finaly preparations to the resposne object
     * so that it is suitable for delivery to the client. This
     * method returns an array of [status, headers, body].
     *
     * @return array[int $status, array $headers, string|resource $body]
     */
    public function finalize()
    {
        // Prepare response
        if (in_array($this->status, array(204, 304))) {
            $this->headers->remove('Content-Type');
            $this->headers->remove('Content-Length');
            $this->setBody('');
        }

        return array($this->status, $this->headers, $this->body);
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
    public function redirect ($url, $status = 302)
    {
        $this->setStatus($status);
        $this->headers->set('Location', $url);
    }

    /**
     * Helpers: Empty?
     * @return bool
     * @api
     */
    public function isEmpty()
    {
        return in_array($this->status, array(201, 204, 304));
    }

    /**
     * Helpers: Informational?
     * @return bool
     * @api
     */
    public function isInformational()
    {
        return $this->status >= 100 && $this->status < 200;
    }

    /**
     * Helpers: OK?
     * @return bool
     * @api
     */
    public function isOk()
    {
        return $this->status === 200;
    }

    /**
     * Helpers: Successful?
     * @return bool
     * @api
     */
    public function isSuccessful()
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Helpers: Redirect?
     * @return bool
     * @api
     */
    public function isRedirect()
    {
        return in_array($this->status, array(301, 302, 303, 307));
    }

    /**
     * Helpers: Redirection?
     * @return bool
     * @api
     */
    public function isRedirection()
    {
        return $this->status >= 300 && $this->status < 400;
    }

    /**
     * Helpers: Forbidden?
     * @return bool
     * @api
     */
    public function isForbidden()
    {
        return $this->status === 403;
    }

    /**
     * Helpers: Not Found?
     * @return bool
     * @api
     */
    public function isNotFound()
    {
        return $this->status === 404;
    }

    /**
     * Helpers: Client error?
     * @return bool
     * @api
     */
    public function isClientError()
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Helpers: Server Error?
     * @return bool
     * @api
     */
    public function isServerError()
    {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * Get message for HTTP status code
     * @param  int         $status
     * @return string|null
     */
    public static function getMessageForCode($status)
    {
        if (isset(self::$messages[$status])) {
            return self::$messages[$status];
        }

        return null;
    }

    /**
     * Convert response to string
     * @return string
     */
    public function __toString()
    {
        $output = sprintf('HTTP/1.1 %s', static::getMessageForCode($this->getStatus())) . PHP_EOL;
        foreach ($this->headers as $name => $value) {
            $output .= sprintf('%s: %s', $name, $value) . PHP_EOL;
        }
        $body = $this->getBody();
        if ($body) {
            $output .= PHP_EOL . $body;
        }

        return $output;
    }
}
