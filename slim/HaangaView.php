<?php

// require in slim and templates_cache folder

class HaangaView extends View
{
    public function render($template)
    {
        Haanga::configure(array(
            'template_dir' => Slim::root() . 'templates',
            'cache_dir' => Slim::root() . 'templates_cache',
        ));
        
        Haanga::Load($template, $this->data);
    }
}
