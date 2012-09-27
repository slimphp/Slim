<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.0.0
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

class HttpCache
{
    /**
     * @var $request
     */
    protected $request;

    /**
     * @var $response
     */
     protected $response;

    /**
     * Constructor
     *
     * @param \Slim\Request $request
     * @param \Slim\Response $response
     */
    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /********************************************************************************
    * HTTP Caching
    *******************************************************************************/

    /**
     * Set Last-Modified HTTP Response Header
     *
     * Set the HTTP 'Last-Modified' header and stop if a conditional
     * GET request's `If-Modified-Since` header matches the last modified time
     * of the resource. The `time` argument is a UNIX timestamp integer value.
     * When the current request includes an 'If-Modified-Since' header that
     * matches the specified last modified time, the application will stop
     * and send a '304 Not Modified' response to the client.
     *
     * @param  int                       $time The last modified UNIX timestamp
     * @throws \InvalidArgumentException If provided timestamp is not an integer
     */
    public function lastModified($time)
    {
        if (is_integer($time)) {
            $this->response['Last-Modified'] = date(DATE_RFC1123, $time);
            if ($time === strtotime($this->request->headers('IF_MODIFIED_SINCE'))) {
                $this->halt(304);
            }
        } else {
            throw new \InvalidArgumentException('Slim::lastModified only accepts an integer UNIX timestamp value.');
        }
    }

    /**
     * Set ETag HTTP Response Header
     *
     * Set the etag header and stop if the conditional GET request matches.
     * The `value` argument is a unique identifier for the current resource.
     * The `type` argument indicates whether the etag should be used as a strong or
     * weak cache validator.
     *
     * When the current request includes an 'If-None-Match' header with
     * a matching etag, execution is immediately stopped. If the request
     * method is GET or HEAD, a '304 Not Modified' response is sent.
     *
     * @param  string                    $value The etag value
     * @param  string                    $type  The type of etag to create; either "strong" or "weak"
     * @throws \InvalidArgumentException If provided type is invalid
     */
    public function etag($value, $type = 'strong')
    {
        //Ensure type is correct
        if (!in_array($type, array('strong', 'weak'))) {
            throw new \InvalidArgumentException('Invalid Slim::etag type. Expected "strong" or "weak".');
        }

        //Set etag value
        $value = '"' . $value . '"';
        if ($type === 'weak') $value = 'W/'.$value;
        $this->response['ETag'] = $value;

        //Check conditional GET
        if ($etagsHeader = $this->request->headers('IF_NONE_MATCH')) {
            $etags = preg_split('@\s*,\s*@', $etagsHeader);
            if (in_array($value, $etags) || in_array('*', $etags)) {
                $this->halt(304);
            }
        }
    }

    /**
     * Set Expires HTTP response header
     *
     * The `Expires` header tells the HTTP client the time at which
     * the current resource should be considered stale. At that time the HTTP
     * client will send a conditional GET request to the server; the server
     * may return a 200 OK if the resource has changed, else a 304 Not Modified
     * if the resource has not changed. The `Expires` header should be used in
     * conjunction with the `etag()` or `lastModified()` methods above.
     *
     * @param string|int    $time   If string, a time to be parsed by `strtotime()`;
     *                              If int, a UNIX timestamp;
     */
    public function expires($time)
    {
        if (is_string($time)) {
            $time = strtotime($time);
        }
        $this->response['Expires'] = gmdate(DATE_RFC1123, $time);
    }

    /**
     * Halt
     *
     * Stop the application and immediately send the response with a
     * specific status and body to the HTTP client. This may send any
     * type of response: info, success, redirect, client error, or server error.
     *
     * @param  int      $status     The HTTP response status
     * @param  string   $message    The HTTP response body
     */
    public function halt($status, $message = '')
    {
        ob_clean();
        $this->response->status($status);
        $this->response->body($message);
        throw new \Slim\Exception\Stop();
    }
}
