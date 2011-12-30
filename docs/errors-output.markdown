# Error Output [errors-output] #

The Slim environment will always contain a key **slim.errors** with a value that is a writable resource to which log and error messages may be written. The Slim application's default [Log](#logging) will write log messages to **slim.errors** whenever an Exception is caught or the Log is manually invoked.

If you want to redirect error output to a different location, you can define your own writable resource by modifying the Environment settings. I recommend you use [middleware](#middleware) to update the Environment like this:

    class CustomErrorMiddleware implements Slim_Middleware_Interface {
        protected $app;
        protected $settings;
        public function __construct( $app, $settings = array() ) {
            $this->app = $app;
            $this->settings = $settings;
        }
        public function call( &$env ) {
            $env['slim.errors'] = fopen('/path/to/custom/output', 'w');
            return $this->app->call($env);
        }
    }

Remember, **slim.errors** does not have to point to a file; it can point to any valid writable resource.