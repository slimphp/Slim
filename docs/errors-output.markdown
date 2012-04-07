# Error Output [errors-output] #

The Slim environment will always contain a key **slim.errors** with a value that is a writable resource to which log and error messages may be written. The Slim application's [Slim_Log](#logging) object will write log messages to **slim.errors** whenever an Exception is caught or the `Slim_Log` object is manually invoked.

If you want to redirect error output to a different location, you can define your own writable resource by modifying the Slim application's environment settings. I recommend you use [middleware](#middleware) to update the environment like this:

    class CustomErrorMiddleware extends Slim_Middleware {
        public function call() {
            // Set new error output
            $env = $this->app->environment();
            $env['slim.errors'] = fopen('/path/to/custom/output', 'w');
            
            // Call next middleware
            $this->next->call();
        }
    }

Remember, **slim.errors** does not have to point to a file; it can point to any valid writable resource.