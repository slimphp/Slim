# Halt [routing-helpers-halt] #

The `halt()` application instance method will immediately return an HTTP response with a given status code and body. This method accepts two arguments: the HTTP status code and an optional message. Slim will immediately halt the current application and send an HTTP response to the client with the specified status and optional message (as the response body). This will override the existing Response object.

    $app = new Slim();

    //Send a default 500 error response
    $app->halt(500);

    //Or if you encounter a Balrog...
    $app->halt(403, 'You shall not pass!');

If you would like to render a template with a list of error messages, you should use the `render()` application instance method instead.

    $app = new Slim();
    $app->get('/foo', function () use ($app) {
        $errorData = array('error' => 'Permission Denied');
        $app->render('errorTemplate.php', $errorData, 403);
    });
    $app->run();

The `halt()` application instance method may send any type of HTTP response to the client: informational, success, redirect, not found, client error, or server error.