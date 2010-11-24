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
 * Slim View
 *
 * The View is delegated the responsibility of rendering a template. Usually
 * you will subclass View and, in the subclass, re-implement the render
 * method to use a custom templating engine, such as Smarty, Twig, Markdown, etc.
 *
 * It is very important that the View *echo* the final template output. DO NOT
 * return the output... if you return the output rather than echoing it, the
 * Slim Response body will be empty.
 *
 * @package	Slim
 * @author	Josh Lockhart <info@joshlockhart.com>
 * @since	Version 1.0
 */
class View {

	/**
	 * @var array Associative array of data available to the template
	 */
	protected $data = array();

	/**
	 * @var string The templates directory
	 */
	protected $templatesDirectory;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/***** ACCESSORS *****/

	/**
	 * Get data
	 *
	 * @param string $key
	 * @return array
	 */
	public function getData( $key = null ) {
		if ( !is_null($key) ) {
			return isset($this->data[$key]) ? $this->data[$key] : null;
		} else {
			return $this->data;
		}
	}

	/**
	 * Set data
	 *
	 * @param array $data
	 * @return void
	 */
	public function setData( $data ) {
		if ( is_array($data) ) {
			$this->data = $data;
		}
	}

	/**
	 * Append data
	 *
	 * @param array $data
	 * @return void
	 */
	public function appendData( $data ) {
		if ( is_array($data) ) {
			$this->data = array_merge($this->data, $data);
		}
	}

	/**
	 * Get templates directory
	 *
	 * @return string|null
	 */
	public function getTemplatesDirectory() {
		return $this->templatesDirectory;
	}

	/**
	 * Set templates directory
	 *
	 * @param string $dir
	 * @return void
	 */
	public function setTemplatesDirectory( $dir ) {
		if ( !is_dir($dir) ) {
			throw new RuntimeException('Cannot set View templates directory to: ' . $dir . '. Directory does not exist.');
		}
		$this->templatesDirectory = rtrim($dir, '/');
	}

	/***** RENDERING *****/

	/**
	 * Display template
	 *
	 * @return void
	 */
	public function display( $template ) {
		echo $this->render($template);
	}

	/**
	 * Render template
	 *
	 * @param	string $template Path to template file, relative to View::$templatesDirectory
	 * @return 	string Rendered template
	 */
	public function render( $template ) {
		extract($this->data);
		$templatePath = $this->getTemplatesDirectory() . '/' . ltrim($template, '/');
		if ( !file_exists($templatePath) ) {
			throw new RuntimeException('View cannot render template `' . $templatePath . '`. Template does not exist.');
		}
		ob_start();
		require $templatePath;
		return ob_end_clean();
	}

}
?>