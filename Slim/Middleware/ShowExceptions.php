<?php
class Slim_Middlware_ShowExceptions {
    private $app;

    public function __construct($app) {
        $this->app = $app;
    }

    public function call($env) {
        try {
            return $this->app->call($env);
        } catch ( Exception $e ) {
            $body = $this->format($e, $env);
            return array(500, array('Content-type' => 'text/html', 'Content-length' => strlen($body)), $body);
        }
    }

    private function format( Exception $e, $env ) {
        return sprintf('<html><body><h1>%s Exception</h1><p>%s</p></body></html>', $e->getCode(), $e->getMessage());
    }
}
?>