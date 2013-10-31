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

/**
 * View
 *
 * This class is responsible for fetching and rendering a template with
 * a given set of data. Although the `\Slim\View` class is itself
 * capable of rendering PHP templates, it is highly recommended that you
 * subclass `\Slim\View` for use with popular PHP templating libraries
 * such as Twig, Smarty, or Mustache.
 *
 * If you do choose to create a subclass of `\Slim\View`, the subclass
 * MUST override the `render` method with this exact signature:
 *
 *     public render(string $template);
 *
 * The `render` method MUST return the rendered output for the template
 * identified by the `$template` argument. The `$template` argument will
 * contain the template file pathname *relative to* the templates base
 * directory for the current view instance.
 *
 * The `Slim-Views` repository contains pre-made custom views for
 * Twig and Smarty, two of the most popular PHP templating libraries.
 *
 * See: https://github.com/codeguy/Slim-Views
 *
 * Also, `\Slim\View` extends `\Slim\Container` so
 * that you may use the convenient `\Slim\Container` interface just
 * as you do with other Slim application data sets (e.g. HTTP headers,
 * HTTP cookies, etc.)
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class View extends \Slim\Collection
{
    /**
     * Constructor
     * @param  string $templateDirectory Path to template directory
     * @param  array  $items             Initialize set with these items
     * @api
     */
    public function __construct($templateDirectory, array $items = array())
    {
        $this->templateDirectory = rtrim($templateDirectory, DIRECTORY_SEPARATOR);
        parent::__construct($items);
    }

    /**
     * Display template
     *
     * This method echoes the rendered template to the current output buffer
     *
     * @param  string $template Pathname of template file relative to templates directory
     * @param  array  $items    Expose these array items to the rendered template
     * @api
     */
    public function display($template, array $data = array())
    {
        echo $this->fetch($template, $data);
    }

    /**
     * Fetch template
     *
     * This method returns the rendered template. This is useful if you need to capture
     * a rendered template into a variable for futher processing.
     *
     * @var    string $template Pathname of template file relative to templates directory
     * @param  array  $items    Expose these array items to the rendered template
     * @return string           The rendered template
     * @api
     */
    public function fetch($template, array $data = array())
    {
        return $this->render($template, $data);
    }

    /**
     * Render template
     *
     * This method will render the specified template file using the current application view.
     * Although this method will work perfectly fine, it is recommended that you create your
     * own custom view class that implements \Slim\ViewInterface instead of using this default
     * view class. This default implementation is largely intended as an example.
     *
     * @var    string            $template Pathname of template file relative to templates directory
     * @return string                      The rendered template
     * @throws \RuntimeException           If resolved template pathname is not a valid file
     */
    protected function render($template, array $data = array())
    {
        // Resolve and verify template file
        $templatePathname = $this->templateDirectory . DIRECTORY_SEPARATOR . ltrim($template, DIRECTORY_SEPARATOR);
        if (!is_file($templatePathname)) {
            throw new \RuntimeException("Cannot render template `$templatePathname` because the template does not exist. Make sure your view's template directory is correct.");
        }

        // Render template with view variables into a temporary output buffer
        $this->replace($data);
        extract($this->all());
        ob_start();
        require $templatePathname;

        // Return temporary output buffer content, destroy output buffer
        return ob_get_clean();
    }
}
