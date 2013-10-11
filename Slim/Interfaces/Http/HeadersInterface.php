<?php

namespace Slim\Interfaces\Http;

interface HeadersInterface
{
    public static function extract($data);

    protected function normalizeKey($key);
}
