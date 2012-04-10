# Add Middleware [middleware-add] #

Use the Slim application's `add()` instance method to add new middleware to a Slim application. New middleware will surround previously added middleware, or the Slim application itself if no middleware has yet been added.

## Example Middleware ##

    class Secret_Middleware extends Slim_Middleware {
        public function call() {
            $app = $this->app;
            $req = $app->request();
            $res = $app->response();
            if ( $req->headers('X-Secret-Request') === 'The sun is shining' ) {
                $res->header('X-Secret-Response', 'But the ice is slippery');
            }
            $this->next->call();
        }
    }

## Add Middleware ##

    $app = new Slim();
    $app->add(new Secret_Middleware());
    $app->get('/foo', function () use ($app) {
        //Do something
    });
    $app->run();

The Slim application's `add()` method accepts one argument: a middleware instance. If the middleware instance requires special configuration, it may implement its own constructor so that it may be configured before it is added to the Slim application.