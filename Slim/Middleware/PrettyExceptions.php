<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.2.0
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
namespace Slim\Middleware;

/**
 * Pretty Exceptions
 *
 * This middleware catches any Exception thrown by the surrounded
 * application and displays a developer-friendly diagnostic screen.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class PrettyExceptions extends \Slim\Middleware
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     * @param array $settings
     */
    public function __construct($settings = array())
    {
        $this->settings = $settings;
    }

    /**
     * Call
     */
    public function call()
    {
        try {
            $this->next->call();
        } catch (\Exception $e) {
            $env = $this->app->environment();
            $env['slim.log']->error($e);
            $this->app->contentType('text/html');
            $this->app->response()->status(500);
            $this->app->response()->body($this->renderBody($env, $e));
        }
    }

    /**
     * Render response body
     * @param  array      $env
     * @param  \Exception $exception
     * @return string
     */
    protected function renderBody(&$env, $exception)
    {
        // Check for cURL user-agent to detect whether to send HTML or not:
        $send_html = true;
        if (substr($_SERVER['HTTP_USER_AGENT'], 0, 4) == 'curl') {
            $send_html = false;
        }
        
        $title = 'Slim Application Error';
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        if ($send_html) {
            $content = sprintf('<h1>%s</h1>', $title);
            $content .= '<p>The application could not run because of the following error:</p>';
            $content .= '<h2>Details</h2>';
            $content .= sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));
        } else {
            $content = "The application could not run because of the following error:\n";
            $content .= sprintf("Type:     %s\n", get_class($exception));
        }
        
        if ($code) {
            $content .= $send_html ? sprintf('<div><strong>Code:</strong> %s</div>', $code) :
                                     sprintf("Code:     %s\n", $code);
        }
        if ($message) {
            $content .= $send_html ? sprintf('<div><strong>Message:</strong> %s</div>', $message) :
                                     sprintf("Message:  %s\n", $message);
        }
        if ($file) {
            $content .= $send_html ? sprintf('<div><strong>File:</strong> %s</div>', $file) :
                                     sprintf("File:     %s\n", $file);
        }
        if ($line) {
            $content .= $send_html ? sprintf('<div><strong>Line:</strong> %s</div>', $line) :
                                     sprintf("Line:     %s\n\n", $line);
        }
        if ($trace) {
            $content .= $send_html ? '<h2>Trace</h2>' :
                                     "Trace: \n";
            $content .= $send_html ? sprintf('<pre>%s</pre>', $trace) :
                                     $trace;
        }
        
        if ($send_html) {
            return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $content);
        } else {
            return sprintf("%s\n\n%s\n", $title, $content);
        }
    }
}
