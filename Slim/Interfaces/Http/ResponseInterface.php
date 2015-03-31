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

use Psr\Http\Message\ResponseInterface as HttpMessageResponseInterface;

/**
 * Slim Request Interface
 *
 * @package Slim
 * @author  Jeremy Kendall
 * @since   3.0.0
 */
interface ResponseInterface extends HttpMessageResponseInterface
{
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
    public function getCookies();

    /**
     * Checks if a cookie exists by the given case-insensitive name.
     *
     * @param  string $name Case-insensitive header name.
     * @return bool         Returns true if any cookie names match the given cookie
     *                      name using a case-insensitive string comparison. Returns false if
     *                      no matching cookie name is found in the message.
     */
    public function hasCookie($name);

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
    public function getCookie($name);

    /**
     * Retrieves a cookie by the given case-insensitive name as an array of properties.
     *
     * @param  string        $name Case-insensitive cookie name.
     * @return string[]|null
     */
    public function getCookieProperties($name);

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
    public function withCookie($name, $value);

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
    public function withoutCookie($name);

    /**
     * Write data to the response body
     *
     * Proxies to the underlying stream and writes the provided data to it.
     *
     * @param string $data
     */
    public function write($data);

    /**
     * Finalize response for delivery to client
     *
     * @return self
     */
    public function finalize();

    /**
     * Send HTTP headers to client
     *
     * @return self
     */
    public function sendHeaders();

    /**
     * Send HTTP body to client
     *
     * @param  int $bufferSize
     * @return self
     */
    public function sendBody($bufferSize = 1024);

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
    public function withRedirect($url, $status = 302);

    /**
     * Is this response empty?
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Is this response informational?
     *
     * @return bool
     */
    public function isInformational();

    /**
     * Is this response OK?
     *
     * @return bool
     */
    public function isOk();

    /**
     * Is this response successful?
     *
     * @return bool
     */
    public function isSuccessful();

    /**
     * Is this response a redirect?
     *
     * @return bool
     */
    public function isRedirect();

    /**
     * Is this response a redirection?
     *
     * @return bool
     */
    public function isRedirection();

    /**
     * Is this response forbidden?
     *
     * @return bool
     * @api
     */
    public function isForbidden();

    /**
     * Is this response not Found?
     *
     * @return bool
     */
    public function isNotFound();

    /**
     * Is this response a client error?
     *
     * @return bool
     */
    public function isClientError();

    /**
     * Is this response a server error?
     *
     * @return bool
     */
    public function isServerError();
}
