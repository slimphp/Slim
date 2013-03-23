<?php
namespace Slim\Http;

class Cookies extends \Slim\Helper\Set
{
    protected $defaults = array(
        'value' => '',
        'domain' => null,
        'path' => null,
        'expires' => null,
        'secure' => false,
        'httponly' => false
    );

    public function set($key, $value)
    {
        if (is_array($value)) {
            $cookieSettings = array_replace($this->defaults, $value);
        } else {
            $cookieSettings = array_replace($this->defaults, array('value' => $value));
        }
        parent::set($key, $cookieSettings);
    }

    public function remove($key, $settings = array())
    {
        $settings['value'] = '';
        $settings['expires'] = time() - 86400;
        $this->set($key, array_replace($this->defaults, $settings));
    }
}
