<?php
class Slim_Middleware_SessionCookie {
    private $app;
    private $settings;

    public function __construct($app, $settings = array()) {
        $this->app = $app;
        $this->settings = $settings;
    }

    public function call($env) {
        if ( session_id() === '' ) {
            session_cache_limiter(false);
            session_start();
            list($status, $headers, $body) = $this->app->call($env);
            //TODO: Persist session with given settings
            return array($status, $headers, $body);
        } else {
            $this->app->call($env);
        }
    }
}
?>