<?php

namespace Slim\Tests\Mocks;

use Slim\Interfaces\GlobalUtilInterface;

class MockGlobalUtil implements GlobalUtilInterface
{
    private $headers;
    private $headersSent;
    private $connectionStatus;

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param mixed $headersSent
     * @return MockGlobalUtil
     */
    public function setHeadersSent($headersSent)
    {
        $this->headersSent = $headersSent;

        return $this;
    }

    /**
     * @param mixed $connectionStatus
     * @return MockGlobalUtil
     */
    public function setConnectionStatus($connectionStatus)
    {
        $this->connectionStatus = $connectionStatus;

        return $this;
    }


    public function header($string, $replace = true)
    {
        $this->headers[] = [$string, $replace];
    }

    public function headersSent()
    {
        return $this->headersSent;
    }

    public function connectionStatus()
    {
        return $this->connectionStatus();
    }
}
