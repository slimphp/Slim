<?php
/**
 * Slim
 *
 * A simple PHP framework for PHP 5 or newer
 *
 * @author		Josh Lockhart <info@joshlockhart.com>
 * @link		http://slim.joshlockhart.com
 * @copyright	2010 Josh Lockhart
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
 * TwigView
 *
 * The TwigView is a Custom View class that renders templates using the Twig 
 * template language (http://www.twig-project.org/).
 */
class TwigView extends View {

    /**
     * @var TwigEnvironment The Twig environment for rendering templates.
     */
    private $twigEnvironment = null;

    /**
     * @var array The options for the Twig environment, see 
     * http://www.twig-project.org/book/03-Twig-for-Developers
     */
    public  $twigOptions = array();

    /**
     * @var array The location of the Twig code directory.
     * Defaults to __DIR__/../lib/Twig/lib/Twig in TwigView::getTwigDirectory().
     */
    public  $twigDirectory = null;

    public function render( $template ) {
        $env = $this->getEnvironment();
        $template = $env->loadTemplate($template); 
        echo $template->render($this->data);
    }

    /**
     * Creates new TwigEnvironment if it doesn't already exist, and returns it.
     *
     * @return TwigEnvironment
     */
    private function getEnvironment() {
        if( !$this->twigEnvironment ) {
            $twigDirectory = $this->getTwigDirectory();

            require_once $twigDirectory . '/Autoloader.php';
            Twig_Autoloader::register();
            $loader = new Twig_Loader_Filesystem($this->templatesDirectory());

            $this->twigEnvironment = new Twig_Environment(
                $loader, 
                $this->twigOptions
            );
        }
        return $this->twigEnvironment;
    }

    /**
     * Get the Twig directory, and set it to a reasonable default if it isn't 
     * already set.
     *
     * @return string path to the Twig directory.
     */
    private function getTwigDirectory() {
        if( !$this->twigDirectory ) {
            $this->twigDirectory = dirname(__FILE__).'/../lib/Twig/lib/Twig';
        }
        return $this->twigDirectory;
    }
}