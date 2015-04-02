<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Http;

use Slim\Interfaces\Http\HeadersInterface;
use Slim\Interfaces\Http\CookiesInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamableInterface;

/**
 * Response
 *
 * This class represents an HTTP response. It manages
 * the response status, headers, cookies, and body
 * according to the PSR-7 standard.
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/MessageInterface.php
 * @link https://github.com/php-fig/http-message/blob/master/src/RequestInterface.php
 */
class Response implements ResponseInterface
{
    /**
     * Protocol version
     *
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * Status code
     *
     * @var int
     */
    protected $status = 200;

    /**
     * Headers
     *
     * @var \Slim\Interfaces\Http\HeadersInterface
     */
    protected $headers;

    /**
     * Cookies
     *
     * @var \Slim\Interfaces\Http\CookiesInterface
     */
    protected $cookies;

    /**
     * Body object
     *
     * @var \Psr\Http\Message\StreamableInterface
     */
    protected $body;

    /**
     * Status codes and reason phrases
     *
     * @var array
     */
    protected static $messages = [
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
        511 => 'Network Authentication Required',
    ];
    
    /**
     * Redirect response factory
     *
     * This method returns a new 3XX HTTP response object to specific URL.
     *
     * @param string $url    The destination URL
     * @param int    $status The HTTP redirect status code (optional)
     *
     * @return self
     */
    public static function redirect($url, $status = 302)
    {
        $headers = new Headers(['Location' => $url]);
        
        return new static($status, $headers);
    }

    /**
     * Create new HTTP response
     *
     * @param int                      $status  The response status code
     * @param HeadersInterface|null    $headers The response headers
     * @param CookiesInterface|null    $cookies The response cookies
     * @param StreamableInterface|null $body    The response body
     */
    public function __construct($status = 200, HeadersInterface $headers = null, CookiesInterface $cookies = null, StreamableInterface $body = null)
    {
        $this->status = $this->filterStatus($status);
        $this->headers = $headers ? $headers : new Headers();
        $this->cookies = $cookies ? $cookies : new Cookies();
        $this->body = $body ? $body : new Body(fopen('php://temp', 'r+'));
    }

    /**
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
     * @param  string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version)
    {
        static $valid = [
            '1.0' => true,
            '1.1' => true,
            '2.0' => true,
        ];
        if (!isset($valid[$version])) {
            throw new \InvalidArgumentException('Invalid HTTP version. Must be one of: 1.0, 1.1, 2.0');
        }
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
     * @link  http://tools.ietf.org/html/rfc7231#section-6
     * @link  http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param integer     $code         The 3-digit integer result code to set.
     * @param null|string $reasonPhrase The reason phrase to use with the
     *                                  provided status code; if none is provided, implementations MAY
     *                                  use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = null)
    {
        $code = $this->filterStatus($code);
        $clone = clone $this;
        $clone->status = $code;
        // NOTE: We ignore custom reason phrases for now. Why? Because.

        return $clone;
    }

    /**
     * Filter HTTP status code
     *
     * @param  int $status HTTP status code
     * @return int
     * @throws \InvalidArgumentException If invalid HTTP status code
     */
    protected function filterStatus($status)
    {
        if (!is_integer($status) || !isset(static::$messages[$status])) {
            throw new \InvalidArgumentException('Invalid HTTP status code');
        }

        return $status;
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
     * @link   http://tools.ietf.org/html/rfc7231#section-6
     * @link   http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
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
     * @param  string $name Case-insensitive header name.
     * @return bool         Returns true if any header names match the given header
     *                      name using a case-insensitive string comparison. Returns false if
     *                      no matching header name is found in the message.
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
     * @param  string $name Case-insensitive header name.
     * @return string
     */
    public function getHeader($name)
    {
        return implode(',', $this->headers->get($name));
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param  string   $name Case-insensitive header name.
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
     * @param  string          $header Header name
     * @param  string|string[] $value  Header value(s).
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
     * @param  string          $header Header name to add
     * @param  string|string[] $value  Header value(s).
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
     * @param  string $header HTTP header to remove
     * @return self
     */
    public function withoutHeader($header)
    {
        $clone = clone $this;
        $clone->headers->remove($header);

        return $clone;
    }

    /*******************************************************************************
     * Cookies
     ******************************************************************************/

    /**
     * Retrieves all cookies.
     *
     * The keys represent the cookie name as it will be sent over the wire, and
     * each value is an array of properties associated with the cookie.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getCookies() as $name => $values) {
     *         echo $values['value'];
     *         echo $values['expires'];
     *         echo $values['path'];
     *         echo $values['domain'];
     *         echo $values['secure'];
     *         echo $values['httponly'];
     *     }
     *
     * @return array Returns an associative array of the cookie's properties. Each
     *               key MUST be a cookie name, and each value MUST be an array of properties.
     */
    public function getCookies()
    {
        return $this->cookies->all();
    }

    /**
     * Checks if a cookie exists by the given case-insensitive name.
     *
     * @param  string $name Case-insensitive header name.
     * @return bool         Returns true if any cookie names match the given cookie
     *                      name using a case-insensitive string comparison. Returns false if
     *                      no matching cookie name is found in the message.
     */
    public function hasCookie($name)
    {
        return $this->cookies->has($name);
    }

    /**
     * Retrieve a cookie by the given case-insensitive name, as a string.
     *
     * This method returns all of the cookie values of the given
     * case-insensitive cookie name as a string as it will
     * appear in the HTTP response's `Set-Cookie` header.
     *
     * @param  string      $name Case-insensitive cookie name.
     * @return string|null
     */
    public function getCookie($name)
    {
        return $this->cookies->getAsString($name);
    }

    /**
     * Retrieves a cookie by the given case-insensitive name as an array of properties.
     *
     * @param  string        $name Case-insensitive cookie name.
     * @return string[]|null
     */
    public function getCookieProperties($name)
    {
        return $this->cookies->get($name);
    }

    /**
     * Create a new instance with the provided cookie, replacing any existing
     * values of any cookies with the same case-insensitive name.
     *
     * While cookie names are case-insensitive, the casing of the cookie will
     * be preserved by this function, and returned from getCookies().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new and/or updated cookie and value.
     *
     * @param  string          $name  Cookie name
     * @param  string|string[] $value Cookie value(s).
     * @return self
     */
    public function withCookie($name, $value)
    {
        $clone = clone $this;
        $clone->cookies->set($name, $value);

        return $clone;
    }

    /**
     * Creates a new instance, without the specified cookie.
     *
     * Cookie resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the named cookie.
     *
     * @param  string $name HTTP cookie to remove
     * @return self
     */
    public function withoutCookie($name)
    {
        $clone = clone $this;
        $clone->cookies->remove($name);

        return $clone;
    }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    /**
     * Write data to the response body
     *
     * Proxies to the underlying stream and writes the provided data to it.
     *
     * @param string $data
     */
    public function write($data)
    {
        $this->getBody()->write($data);

        return $this;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamableInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Create a new instance, with the specified message body.
     *
     * The body MUST be a StreamableInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param  StreamableInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamableInterface $body)
    {
        // TODO: Test for invalid body?
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /*******************************************************************************
     * Response Helpers
     ******************************************************************************/

    /**
     * Finalize response for delivery to client
     *
     * @return self
     */
    public function finalize()
    {
        $response = $this;

        // Serialize cookies
        $cookies = $response->getCookies();
        foreach ($cookies as $name => $properties) {
            $response = $response->withAddedHeader('Set-Cookie', $response->getCookie($name));
        }

        // Finalize headers
        if (in_array($response->getStatusCode(), [204, 304])) {
            $response = $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        } else {
            $size = $response->getBody()->getSize();
            if ($size !== null) {
                $response = $response->withHeader('Content-Length', $size);
            }
        }

        return $response;
    }

    /**
     * Send HTTP headers to client
     *
     * @return self
     */
    public function sendHeaders()
    {
        if (headers_sent() === false) {
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf(
                    'Status: %s %s',
                    $this->getStatusCode(),
                    $this->getReasonPhrase()
                ));
            } else {
                header(sprintf(
                    'HTTP/%s %s %s',
                    $this->getProtocolVersion(),
                    $this->getStatusCode(),
                    $this->getReasonPhrase()
                ));
            }

            foreach ($this->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        return $this;
    }

    /**
     * Send HTTP body to client
     *
     * @param  int $bufferSize
     * @return self
     */
    public function sendBody($bufferSize = 1024)
    {
        if (in_array($this->getStatusCode(), [204, 304]) === false) {
            $body = $this->getBody();
            if ($body->isAttached() === true) {
                $body->rewind();
                while ($body->eof() === false) {
                    echo $body->read($bufferSize);
                }
            }
        }

        return $this;
    }

    /**
     * Redirect
     *
     * This method prepares the response object to return an HTTP Redirect response
     * to the client.
     *
     * @param  string $url    The redirect destination
     * @param  int    $status The redirect HTTP status code
     * @return self
     */
    public function withRedirect($url, $status = 302)
    {
        return $this->withStatus($status)->withHeader('Location', $url);
    }

    /**
     * Is this response empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->getStatusCode(), [201, 204, 304]);
    }

    /**
     * Is this response informational?
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Is this response OK?
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->getStatusCode() === 200;
    }

    /**
     * Is this response successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Is this response a redirect?
     *
     * @return bool
     */
    public function isRedirect()
    {
        return in_array($this->getStatusCode(), [301, 302, 303, 307]);
    }

    /**
     * Is this response a redirection?
     *
     * @return bool
     */
    public function isRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Is this response forbidden?
     *
     * @return bool
     * @api
     */
    public function isForbidden()
    {
        return $this->getStatusCode() === 403;
    }

    /**
     * Is this response not Found?
     *
     * @return bool
     */
    public function isNotFound()
    {
        return $this->getStatusCode() === 404;
    }

    /**
     * Is this response a client error?
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Is this response a server error?
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * Convert response to string
     *
     * @return string
     */
    public function __toString()
    {
        $output = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        $output .= PHP_EOL;
        foreach ($this->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $this->getHeader($name)) . PHP_EOL;
        }
        $output .= PHP_EOL;
        $output .= (string)$this->getBody();

        return $output;
    }
}
