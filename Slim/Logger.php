<?php

namespace Slim;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /**
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        error_log($message);
    }
}
