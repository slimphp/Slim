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
class View
{
    /**
     * @var string Absolute or relative filesystem path to a specific template
     *
     * DEPRECATION WARNING!
     * This variable will be removed in the near future
     */
    protected $templatePath = '';

    /**
     * @var array Associative array of template variables
     */
    protected $data = array();

    /**
     * @var string Absolute or relative path to the application's templates directory
     */
    protected $templatesDirectory;

    /**
     * @var array supported keywords in templating
     */
    protected $compilers = array(
                            'comments',
                            'echos',
                            'else',
                            'structure_start',
                            'structure_end'
                        );

    /**
     * Constructor
     *
     * This is empty but may be implemented in a subclass
     */
    public function __construct()
    {

    }

    /**
     * Get data
     * @param  string|null      $key
     * @return mixed            If key is null, array of template data;
     *                          If key exists, value of datum with key;
     *                          If key does not exist, null;
     */
    public function getData($key = null)
    {
        if (!is_null($key)) {
            return isset($this->data[$key]) ? $this->data[$key] : null;
        } else {
            return $this->data;
        }
    }

    /**
     * Set data
     *
     * If two arguments:
     * A single datum with key is assigned value;
     *
     *     $view->setData('color', 'red');
     *
     * If one argument:
     * Replace all data with provided array keys and values;
     *
     *     $view->setData(array('color' => 'red', 'number' => 1));
     *
     * @param   mixed
     * @param   mixed
     * @throws  InvalidArgumentException If incorrect method signature
     */
    public function setData()
    {
        $args = func_get_args();
        if (count($args) === 1 && is_array($args[0])) {
            $this->data = $args[0];
        } elseif (count($args) === 2) {
            $this->data[(string) $args[0]] = $args[1];
        } else {
            throw new \InvalidArgumentException('Cannot set View data with provided arguments. Usage: `View::setData( $key, $value );` or `View::setData([ key => value, ... ]);`');
        }
    }

    /**
     * Append new data to existing template data
     * @param  array $data
     */
    public function appendData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Get templates directory
     * @return string|null     Path to templates directory without trailing slash;
     *                         Returns null if templates directory not set;
     */
    public function getTemplatesDirectory()
    {
        return $this->templatesDirectory;
    }

    /**
     * Set templates directory
     * @param  string   $dir
     */
    public function setTemplatesDirectory($dir)
    {
        $this->templatesDirectory = rtrim($dir, '/');
    }

    /**
     * Set template
     * @param  string           $template
     * @throws RuntimeException If template file does not exist
     *
     * DEPRECATION WARNING!
     * This method will be removed in the near future.
     */
    public function setTemplate($template)
    {
        $this->templatePath = $this->getTemplatesDirectory() . '/' . ltrim($template, '/');
        if (!file_exists($this->templatePath)) {
            throw new \RuntimeException('View cannot render template `' . $this->templatePath . '`. Template does not exist.');
        }
    }

    /**
     * Display template
     *
     * This method echoes the rendered template to the current output buffer
     *
     * @param  string   $template   Pathname of template file relative to templates directoy
     */
    public function display($template)
    {
        echo $this->fetch($template);
    }

    /**
     * Fetch rendered template
     *
     * This method returns the rendered template
     *
     * @param  string $template Pathname of template file relative to templates directory
     * @return string
     */
    public function fetch($template)
    {
        return $this->render($template);
    }

    /**
     * Render template
     *
     * @param  string   $template   Pathname of template file relative to templates directory
     * @return string
     *
     * @author Gufran <http://github.com/dragon-bird>
     */
    public function render($template)
    {
        // Make sure we include all the required views first
        
        $template = $this->compile_includes($template);

        foreach ($this->compilers as $compiler) {
            $method = 'compile_' . $compiler;
            $template = $this->$method($template);
        }

        extract($this->data) and ob_start();

        try
        {
            eval('?>' . $template);
        }
        catch(\Exception $e)
        {
            ob_get_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * Compile template comments into php comments
     * @param  string $template Template text
     * @return string           compiled template
     *
     * @author Gufran <http://github.com/dragon-bird>
     */
    private function compile_comments($template)
    {
        $template = preg_replace('/\{\{--(.+?)(--\}\})?\n/', "<?php // $1 ?>", $template);
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/', "<?php /* $1 */ ?>\n", $template);
    }

    /**
     * Compile echos in template
     * @param  string $template
     * @return string           compiled template
     *
     * @author Gufran <http://github.com/dragon-bird>
     */
    private function compile_echos($template)
    {
        $template = preg_replace('/\{\{\{(.+?)\}\}\}/', '<?php echo htmlspecialchars($1); ?>', $template);
        return preg_replace('/\{\{(.+?)\}\}/', '<?php echo $1; ?>', $template);
    }

    /**
     * Compile else block in @if..@else
     * @param  string $template 
     * @return string           compiled template
     *
     * @author Gufran <http://github.com/dragon-bird>
     */
    private function compile_else($template)
    {
        return preg_replace('/(\s*)@(else)(\s*)/', '$1<?php $2: ?>$3', $template);
    }

    /**
     * Include template files using @include and return rendered template
     * @param  string $template 
     * @return string           compiled template
     *
     * @author Gufran <http://github.com/dragon-bird>
     */
    private function compile_includes($template)
    {
        $pattern = "/@include\s*\(\s*'(\w+)'\s*\)/";
        $included = array();

        if(preg_match_all($pattern, $template, $included) === 0) {
            return $template;
        }

        $included = $included[1];

        foreach ($included as $view_name) {
            
            $path = $this->getTemplatesDirectory() . '/' . ltrim($view_name, '/') . 'tpl.php';
            
            if (!file_exists($path)) {
                throw new \RuntimeException('View cannot render template `' . $path . '`. Template does not exist.');
            }

            $template = preg_replace("/@include\s*\(\s*'($view_name)'\s*\)/", file_get_contents($path), $template);
        }

        return $template;
    }

    /**
     * Compile various control structures like if,elseif,foreach,for,while
     * @param  string $template 
     * @return string           compiled template
     *
     * @author Gufran <http://github.com/dragon-bird>
     */
    private function compile_structure_start($template)
    {
        $pattern = '/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/';

        return preg_replace($pattern, '$1<?php $2$3: ?>', $template);
    }

    /**
     * close control structures 
     * @param  string $template 
     * @return string           compiled template
     *
     * @author Gufran <http://github.com/dragon-bird>
     */
    private function compile_structure_end($template)
    {
        $pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

        return preg_replace($pattern, '$1<?php $2; ?>$3', $template);
    }

}
