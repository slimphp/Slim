<?php
/**
 * HaangaView
 *
 * The HaangaView is a custom View class that renders templates using the Haanga
 * template language (http://haanga.org/).
 *
 * Properties that you, the developer, will need to change are:
 * - haangaDirectory
 * - haangaTemplatesDirectory
 * - haangaCompiledDirectory
 *
 * Example:
 * {{{
 *      require_once 'views/HaangaView.php';
 *
 *      HaangaView::$haangaDirectory = __DIR__ . '/Haanga';
 *      HaangaView::$haangaTemplatesDirectory = __DIR__ . '/templates';
 *      HaangaView::$haangaCompiledDirectory = __DIR__ . '/tmp';
 *
 *      Slim::init(array(
 *          'view' => new HaangaView()
 *      ));
 * }}}
 *
 * @package Slim
 * @author  Isman Firmansyah
 */
class HaangaView extends View {

    /**
     * @var string The path to the Haanga code directory WITHOUT the trailing slash
     */
    public static $haangaDirectory = null;

    /**
     * @var string The path to the Haanga templates directory WITHOUT the trailing slash
     */
    public static $haangaTemplatesDirectory = null;

    /**
     * @var string The path to the Haanga compiled templates directory WITHOUT the trailing slash
     */
    public static $haangaCompiledDirectory = null;

    /**
     * Render Haanga Template
     *
     * This method will output the rendered template content
     *
     * @param   string $template The path to the Haanga template, relative to the Haanga templates directory.
     * @return  string|NULL
     */
    public function render( $template ) {
        require_once self::$haangaDirectory . '/lib/Haanga.php';
        Haanga::configure(array(
            'template_dir' => self::$haangaTemplatesDirectory,
            'cache_dir' => self::$haangaCompiledDirectory
        ));
        return Haanga::load($template, $this->data);
    }
}
?>
