<?php

namespace Slim\Interfaces;

/**
 * Interface GlobalUtil Interface
 *
 * @package Slim
 * @since 3.5.0
 */
interface GlobalUtilInterface
{
    public function header($string, $replace = true);

    public function headersSent();

    public function connectionStatus();
}
