# Debugging [errors-debug] #

You can enable debugging during application instantiation with this setting:

    $app = new Slim(array(
        'debug' => true
    ));

You may also enable debugging during runtime with the Slim application's `config()` instance method.

    $app = new Slim();

    //Enable debugging (on by default)
    $app->config('debug', true);

    //Disable debugging
    $app->config('debug', false);

If debugging is enabled and an exception or error occurs, a detailed error message will appear with the error description, the affected file, the file line number, and a stack trace. If debugging is disabled, your [custom Error handler](#error-handler) will be invoked instead.