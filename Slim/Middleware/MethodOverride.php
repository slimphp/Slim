<?php
class Slim_Middleware_MethodOverride {
    const HTTP_METHODS = array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS');
    const METHOD_OVERRIDE_KEY = '_METHOD';
    private $app;
    
    public function __construct($app) {
        $this->app = $app;
    }

    public function call($env) {
        if ( isset($env['REQUEST_METHOD']) && $env['REQUEST_METHOD'] === 'POST' ) {
            $req = new Slim_Http_Request($env);
            $method = strtoupper($req->post(self::METHOD_OVERRIDE_KEY));
            if ( $method && in_array($method, self::HTTP_METHODS) ) {
                $env['slim.method_override.original_method'] = $env['REQUEST_METHOD'];
                $env['REQUEST_METHOD'] = $method;
            }
        }
        return $this->app->call($env);
    }
}
?>