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
 * SmartyView
 *
 * The SmartyView is a custom View class that renders templates using the Smarty 
 * template language (http://www.smarty.net).
 *
 * Two fields that you, the developer, will need to change are:
 * - smartyDirectory
 * - smartyCompileDirectory
 * - smartyCacheDirectory
 *
 * @package Slim
 * @author  Jose da Silva <http://josedasilva.net>
 */
class SmartyView extends View {

    /**
     * @var string The path to the Smarty code directory WITHOUT the trailing slash
     */
    public static $smartyDirectory = null;

    /**
     * @var string The path to the Smarty compiled templates folder WITHOUT the trailing slash
     */
    public static $smartyCompileDirectory = null;

    /**
     * @var string The path to the Smarty cache folder WITHOUT the trailing slash
     */
    public static $smartyCacheDirectory = null;

	/**
     * @var instance of the Smarty object
     */
	private $smartyInstance =	null;

	/**
	 * Render Smarty Template
	 *
	 * This method will output the rendered template content
	 *
	 * @param 	string $template The path to the Smarty template, relative to the  templates directory.
	 * @return 	void
	 */
    public function render( $template ) {
        $instance = $this->getInstance();
        $instance->assign($this->data);
        echo $instance->fetch($template);
    }

    /**
     * Creates new Smarty object instance if it doesn't already exist, and returns it.
     *
     * @return Smarty Instance
     */
    private function getInstance() {
        if ( !$this->smartyInstance ) {
            require_once self::$smartyDirectory . '/Smarty.class.php';

			$this->smartyInstance = new Smarty();

			$this->smartyInstance->template_dir = $this->templatesDirectory();
			
			if ( self::$smartyCompileDirectory ) {
				$this->smartyInstance->compile_dir  = self::$smartyCompileDirectory;
			}
			
			if ( self::$smartyCompileDirectory ) {
				$this->smartyInstance->cache_dir  = self::$smartyCacheDirectory;
			}
			
        }

        return $this->smartyInstance;
    }
}

?>