<?php

namespace Slim\Interfaces;

interface ViewInterface
{
    public function display($template, array $data = array());

    public function fetch($template, array $data = array());
}
