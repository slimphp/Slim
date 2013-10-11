<?php

namespace Slim\Interfaces;

interface FlashInterface
{
    public function next($key, $value);

    public function now($key, $value);

    public function keep();

    public function save();

    public function getAll();
}
