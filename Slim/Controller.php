<?php

namespace Slim;

abstract class Controller
{
    /**
     * Instance of the current application
     * @var \Slim\App
     */
    protected $app;

    /**
     * Construct the base for a Controller
     *
     * @param \Slim\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }
}
