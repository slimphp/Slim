<?php
/**
 * HaangaView
 *
 * The HaangaView is a custom View class that renders templates using the Haanga
 * template language (http://haanga.org/).
 *
 * Currently, to use HaangaView, developer must instantiate this class and pass these params:
 * - path to Haanga directory which contain `lib`
 * - path to templates directory
 * - path to compiled templates directory
 *
 * Example:
 * {{{
 *      require_once 'views/HaangaView.php';
 *      Slim::init(array(
 *          'view' => new HaangaView('/path/to/Haanga/dir', '/path/to/templates/dir', '/path/to/compiled/dir')
 *      ));
 * }}}
 *
 * @package Slim
 * @author  Isman Firmansyah
 */
class HaangaView extends View {

    /**
     * Configure Haanga environment
     */
    public function __construct( $haangaDir, $templatesDir, $compiledDir ) {
        require_once $haangaDir . '/lib/Haanga.php';
        Haanga::configure(array(
            'template_dir' => $templatesDir,
            'cache_dir' => $compiledDir
        ));
    }

    /**
     * Render Haanga Template
     *
     * This method will output the rendered template content
     *
     * @param   string $template The path to the Haanga template, relative to the Haanga templates directory.
     * @return  string|NULL
     */
    public function render( $template ) {
        return Haanga::load($template, $this->data);
    }
}
?>
