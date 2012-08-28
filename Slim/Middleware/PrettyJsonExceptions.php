<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
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
 * Pretty JSON Exceptions
 *
 * This middleware catches any Exception thrown by the surrounded
 * application and sets appropriate headers and filters content in
 * order to get a JSON parsable stack trace.
 *
 * @package Slim
 * @author  Carl Helmertz
 * @since   1.0.0
 */
class Slim_Middleware_PrettyJsonExceptions extends Slim_Middleware_PrettyExceptions {

    public function call() {
        try {
            $this->next->call();
        } catch ( Exception $e ) {
            $env = $this->app->environment();
            $env['slim.log']->error($e);
            $this->app->contentType('application/json');
            $this->app->response()->status(500);
            $this->app->response()->body($this->renderBody($env, $e));
        }
    }

    /**
     * Render response body
     * @param array $env
     * @param Exception $exception
     * @return string
     */
    protected function renderBody( &$env, $exception ) {
        return json_encode(array(
            'title' => 'Slim Application Error',
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ));
    }
}
