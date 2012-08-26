# Route Helpers [routing-helpers] #

Slim provides several helper methods (exposed via the `Slim` application instance) that will help you control the flow of your application.

Please be aware that the following application instance method helpers, `halt()`, `pass()`, `redirect()` and `stop()` are implemented using exceptions.  They are wrappers for a `Slim_Exception_Stop` and `Slim_Exception_Pass`.  Throwing the exception in these cases are a simple way to stop user code from processing and have the framework take over and immediately send the necessary response to the client.  The side effect of this can be surprising if unexpected.  Take a look at the following code.

    $app->get('/', function() use ($app, $obj) {
        try {
            $obj->thisMightThrowException();
            $app->redirect('/success');
        } catch(Exception $e) {
            $app->flash('error', $e->getMessage());
            $app->redirect('/error');
        }
    });

If `$obj->thisMightThrowException()` does throw an exception the code will run as expected. However, if no exception is thrown the call to `$app->redirect()` will throw a `Slim_Exception_Stop` which will be caught by the user catch block rather than by the framework thus sending the browser to `/error` page.  Where possible in your own application you should try and use typed exceptions so your catch blocks can be more targeted rather than swallowing all exceptions.  In some situations the `thisMightThrowException()` might be an external component call that you don't control, in which case typing all exceptions thrown may not be feasible.  For these instances we can adjust our code slightly by moving the success `$app->redirect()` after the try/catch block to fix the issues.  Since processing will stop on the error redirect this code will now execute as expected.

    $app->get('/', function() use ($app, $obj) {
        try {
            $obj->thisMightThrowException();
        } catch(Exception $e) {
            $app->flash('error', $e->getMessage());
            $app->redirect('/error');
        }
        $app->redirect('/success');
    });