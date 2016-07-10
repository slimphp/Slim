<?php

namespace Slim;

use Slim\Interfaces\GlobalUtilInterface;

/**
 * GlobalUtil
 *
 * This class contains the functions that wrap PHP global
 * functions that have functionality that is hard to emulate
 * in a test environment.
 */
class GlobalUtil implements GlobalUtilInterface
{
    public function header($string, $replace = true)
    {
        header($string, $replace);
    }

    public function headersSent()
    {
        return headers_sent();
    }

    public function connectionStatus()
    {
        return connection_status();
    }
}
