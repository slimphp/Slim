<?php

namespace Slim\Interfaces\Http;

interface CookiesInterface
{
    public function set($key, $value);

    public function remove($key, $settings = array());

    public function encrypt(\Slim\Crypt $crypt);

    public static function serialize(&$headers, \Slim\Http\Cookies $cookies);

    public static function setHeader(&$header, $name, $value);

    public static function deleteHeader(&$header, $name, $value = array());

    public static function parseHeader($header);
}
