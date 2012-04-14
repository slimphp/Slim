# Error Handler [error-handler] #

You may use the Slim application's `error()` instance method to specify a custom error handler to be invoked when an error or exception occurs. Custom error handlers are only invoked if application debugging is disabled.

A custom error handler should render a user-friendly message that mitigates user confusion. Similar to the Slim application's `notFound()` instance method, the `error()` instance method acts as both a getter and a setter.

## Set a Custom Error Handler ##

You may set a custom error handler by passing a callable into the `error()` instance method as the first and only argument.

    $app = new Slim();

    //PHP >= 5.3
    $app->error(function ( Exception $e ) use ($app) {
        $app->render('error.php');
    });

    //PHP < 5.3
    $app->error('custom_error_handler');
    function custom_error_handler( Exception $e ){
        $app = Slim::getInstance();
        $app->render('error.php');
    }

In this example, notice how the custom error handler accepts the caught Exception as its argument. This allows you to respond appropriately to different exceptions.

## Invoke a Custom Error Handler ##

Usually, the Slim application will automatically invoke the error handler when an exception or error occurs. However, you may also manually invoke the error handler with the Slim application's `error()` method (without an argument).