<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use \Slim\Collection;
use \Slim\Interfaces\ViewInterface;

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
 */
class View extends Collection implements ViewInterface
{
    /**
     * Create new view
     *
     * @param string $templateDirectory Path to template directory
     * @param array  $items             Initialize view with this data
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
     * @param string $template Pathname of template file relative to templates directory
     * @param array  $items    Expose these array items to the rendered template
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
     */
    public function fetch($template, array $data = array())
    {
        return $this->render($template, $data);
    }

    /**
     * Render template
     *
     * This method renders the specified template file using the current application view.
     * Although this method works fine, we recommend that you create a custom
     * view class that implements \Slim\ViewInterface. This default implementation
     * is largely an example implementation and nothing more.
     *
     * @param  string            $template Pathname of template file relative to templates directory
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
        extract($this->all(), EXTR_SKIP);
        ob_start();
        require $templatePathname;

        // Return temporary output buffer content, destroy output buffer
        return ob_get_clean();
    }
}
