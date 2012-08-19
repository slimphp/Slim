<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2012 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.5
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

/**
 * Slim_Negotiator
 * @package Slim
 * @author  Josh Lockhart, Nicholas Humfrey
 * @since   ?
 */
class Slim_Negotiator {

    protected $formatMap = array(
        'atom' => array('application/atom+xml'),
        'css' => array('text/css'),
        'html' => array('text/html', 'application/xhtml+xml'),
        'ics' => array('text/calendar'),
        'js' => array('application/javascript'),
        'json' => array('application/json'),
        'rdf' => array('application/rdf+xml'),
        'rss' => array('application/rss+xml'),
        'ttl' => array('text/turtle'),
        'txt' => array('text/plain'),
        'wml' => array('text/vnd.wap.wml'),
        'xml' => array('application/xml', 'text/xml'),
        'yaml' => array('application/x-yaml')
    );

    protected $formatKey = 'format';

    const ACCEPT_RE = '{
        # media-range
        ((\*|[a-zA-Z0-9.+-]+)/(\*|[a-zA-Z0-9.+-]+))
        # accept-params
        (?:\\s*;\\s*q\\s*=\\s*([01](?:\.[0-9]{1,3})?)
            # accept-extension
            (?:\\s*;\\s*\w+(?:\\s*=\\s*(?:\\w+|"(?:\\"|[^"])*"))?)*
        )?
    }x';

    /**
     * Constructor
     * @return  void
     */
    public function __construct() {
        $defaultConditions = Slim_Route::getDefaultConditions();
        Slim_Route::setDefaultConditions(
          array_merge($defaultConditions, array($this->formatKey => '\.[a-z]{2,8}|'))
        );
    }

    public function setFormatKey($key)
    {
        $this->formatKey = $key;
    }

    public function respondTo($params, $request, $response, $formats) {
        if (isset($params[$this->formatKey])) {
            $format = $params[$this->formatKey];
            // Remove the period from the start
            if (substr($format, 0, 1) == '.') {
                $format = substr($format, 1);
            }
        } else {
            $format = NULL;
        }
        if (!$format) {
            $format = $this->negotiateFormat($request, $response, $formats);
            if (!$format) {
                // Unable to agree on an output format
                $response->status(406);
                $response->header('Content-Type', 'text/plain');
                $response->body("Not Acceptable");
                throw new Slim_Exception_Stop();
            }
        } else if (!in_array($format, $formats)) {
            // Explicit request for an unsupported format
            $response->status(404);
            $response->header('Content-Type', 'text/plain');
            $response->body("Unsupported format");
            throw new Slim_Exception_Stop();
        } else if (isset($this->formatMap[$format])) {
            $mimetype = $this->formatMap[$format][0];
            $response->header('Content-Type', $mimetype);
        }

        return $format;
    }

    protected function negotiateFormat($request, $response, $formats)
    {
        $accept = $request->headers('Accept');
        if (!preg_match_all(self::ACCEPT_RE, $accept, $m, PREG_SET_ORDER)) {
            return NULL;
        }

        // Build a mapping from mime types (eg, "text/html") to the format keys
        // (eg, "html" or "json") provided in $formats.
        $types = array();
        $order = count($formats);
        $primary = array($order, $formats[0]);
        foreach ($formats as $f) {
            if (isset($this->formatMap[$f])) {
                foreach ($this->formatMap[$f] as $type) {
                    if (!isset($types[$type])) {
                        $types[$type] = array($order, $f);
                    }
                }
            }
            $order -= 1;
        }

        // Expand the mapping table to include wildcard matches such as "text/*"
        // and "*/*".
        //
        // PHP arrays are ordered, so array_keys($types) provides the list of
        // mime types in priority order, from most preferred (at index 0) to
        // least preferred (at the end).  The keys are iterated through in
        // reverse order and a new key generated by replacing everything after
        // the "/" with a "*", pointing to the same value.  This ensures that
        // by the end of the loop the highest priority mime type will have
        // claimed the wildcard, ie:
        //
        //   if "text/html" => "html", "text/plain" => "txt":
        //       "text/*" => "html"
        //   if "text/plain" => "txt", "text/html" => "html":
        //       "text/*" => "txt"
        //
        // The "*/*" wildcard always points to the primary format, as determined
        // previously.
        $keys = array_keys($types);
        foreach (array_reverse($keys) as $type) {
            $k = substr($type, 0, strpos($type, '/')) . '/*';
            if (!isset($types[$k])) {
                $types[$k] = $types[$type];
            }
        }
        $types['*/*'] = $primary;

        // Loop through each type mentioned in the chopped-up Accept header,
        // looking for an entry in the mapping table built earlier.  If found,
        // and the q value is greater than the previous match, the corresponding
        // format string (eg, "html") becomes the current candidate match.
        //
        // The q values are adjusted so that with otherwise equal q values, a
        // full wildcard ("*/*") is always lower priority than a partial
        // wildcard (eg, "text/*"), which is always lower than an exact mime
        // type (eg, "text/html").
        //
        // At the end of the loop, $choice (and hence the method return value)
        // will either be one of the strings from $formats or null if there were
        // no matches, eg if the Accept header was "image/png" and the list of
        // formats was just "html".

        $bestq = 0;
        $choice = null;

        foreach ($m as $match) {
            // If not present, q defaults to 1
            $match[] = '1';
            list(, $type, $t, $st, $q) = $match;
            if (!isset($types[$type])) {
                // If the mime type isn't a candidate there's no need to parse
                // the q value
                continue;
            }

            $q = substr($q[0] . substr($q, 2) . '000', 0, 4);
            $order = substr('000' . $types[$type][0], -3);
            if ($t === '*') {
                $q = (int) ($q . '0' . $order);
            } else if ($st === '*') {
                $q = (int) ($q . '1' . $order);
            } else {
                $q = (int) ($q . '2' . $order);
            }

            if ($q > $bestq) {
                $bestq = $q;
                $choice = $type;
            }
        }

        // Response varies based on the Accept request header
        // FIXME: Slim doesn't provide a way of merging header values
        $response->header('Vary', 'Accept');

        if ($choice) {
            $response->header('Content-Type', $choice);
            return $types[$choice][1];
        } else {
            return NULL;
        }
    }
}
