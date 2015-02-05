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
namespace Slim;

use \Slim\Interfaces\EnvironmentInterface;

/**
 * Environment
 *
 * This class determines the environment variables used by
 * the Slim application and lets the Slim application
 * depend on a controlled set of environmental variables that may be
 * mocked, if necessary.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.6.0
 */
class Environment extends Collection implements EnvironmentInterface
{
    /**
     * Mock data for an Environment
     * @var array
     */
    public $mocked = array(
        'SERVER_PROTOCOL'      => 'HTTP/1.1',
        'REQUEST_METHOD'       => 'GET',
        'SCRIPT_NAME'          => '',
        'REQUEST_URI'          => '',
        'QUERY_STRING'         => '',
        'SERVER_NAME'          => 'localhost',
        'SERVER_PORT'          => 80,
        'HTTP_HOST'            => 'localhost',
        'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
        'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
        'HTTP_USER_AGENT'      => 'Slim Framework',
        'REMOTE_ADDR'          => '127.0.0.1',
        'REQUEST_TIME'         => ''
    );

    /**
     * Constructor, will parse an array for environment information if present
     * @param array $environment
     */
    public function __construct($environment = null)
    {
        if (!is_null($environment)) {
            $this->parse($environment);
        }
    }

    /**
     * Parse environment array
     *
     * This method will parse an environment array and add the data to
     * this collection
     *
     * @param  array  $environment
     * @return void
     */
    public function parse(array $environment)
    {
        foreach ($environment as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Mock environment
     *
     * This method will parse a mock environment array and add the data to
     * this collection
     *
     * @param  array  $settings
     * @return void
     */
    public function mock(array $settings = array())
    {
        $this->mocked['REQUEST_TIME'] = time();
        $settings = array_merge($this->mocked, $settings);

        $this->parse($settings);
    }
}
