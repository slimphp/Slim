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
namespace Slim;

/**
 * View
 *
 * The view is responsible for rendering a template. The view
 * should subclass \Slim\View and implement this interface:
 *
 * public render(string $template);
 *
 * This method should render the specified template and return
 * the resultant string.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class View extends \Slim\Container
{
    /**
     * Constructor
     * @param string                     $templateDirectory Path to template directory
     * @throws \InvalidArgumentException If directory is unreadable or not a directory
     */
    public function __construct($templateDirectory = null)
    {
        $this->templateDirectory = rtrim($templateDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * Display template
     *
     * This method echoes the rendered template to the current output buffer
     *
     * @param  string   $template   Pathname of template file relative to templates directory
     */
    public function display($template)
    {
        echo $this->fetch($template);
    }

    /**
     * Return the contents of a rendered template file
     * @var    string $template The template pathname, relative to the template base directory
     * @return string           The rendered template
     */
    public function fetch($template)
    {
        return $this->render($template);
    }

    /**
     * Render a template file
     * @var    string $template     The template pathname, relative to the template base directory
     * @return string               The rendered template
     * @throws \RuntimeException    If resolved template pathname is not a valid file
     */
    protected function render($template)
    {
        $templatePathname = $this->templateDirectory . DIRECTORY_SEPARATOR . ltrim($template, DIRECTORY_SEPARATOR);
        if (!is_file($templatePathname)) {
            throw new \RuntimeException("Cannot render template `$templatePathname` because the template does not exist. Make sure your view's template directory is correct.");
        }

        extract($this->all());
        ob_start();
        require $templatePathname;

        return ob_get_clean();
    }
}
