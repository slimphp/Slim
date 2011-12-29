# Add Middleware [middleware-add] #

Use the Slim application's `add()` instance method to add new middleware around the Slim application. New middleware will surround previously added middleware, or the Slim application itself if no middleware has yet been added.

## Example Middleware ##

This middleware will convert the HTTP response body to uppercase.

    class UpperCaseMiddleware implements Slim_Middleware_Interface {
        protected $app;
        protected $settings;
    
        public function __construct( $app, $settings = array() ) {
            $this->app = $app;
            $this->settings = $settings;
        }

        public function call( &$env ) {
            list($status, $header, $body) = $this->app->call($env);
            return array($status, $header, strtoupper($body));
        }
    }

## Example Application ##

This example demonstrates how to add the above middleware to a Slim application.

    $app = new Slim();
    $app->add('UpperCaseMiddleware');
    $app->get('/', function () {
        echo "hello";
    });
    $app->run();

The first argument to the `add()` method **must** be the class name of the middleware. The middleware must already be required or be discoverable by a registered autoloader.

When this application runs and the resource identified by "/" is invoked, the eventual HTTP response body will be "HELLO".

## Middleware Settings ##

You may also provide an array of settings as the second argument of the Slim application's `add()` instance method to customize the middleware.

    $app = new Slim();
    $app->add('UpperCaseMiddleware', array(
        'foo' => 'bar'
    ));
    $app->get('/', function () {
        echo "hello";
    });
    $app->run();

The array of settings will be passed into the middleware's constructor as its second argument.