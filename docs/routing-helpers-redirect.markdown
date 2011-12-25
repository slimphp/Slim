# Redirect [routing-helpers-redirect] #

It is easy to redirect the client to another URL with the `redirect()` application instance method. This method accepts two arguments: the first argument is the URL to which the client will redirect; the second optional argument is the HTTP status code. By default the `redirect()` application instance method will send an **HTTP 302 Temporary Redirect** response.

    $app = new Slim();
    $app->get('/foo', function () use ($app) {
        $app->redirect('/bar');
    });
    $app->run();

Or if you wish to use a permanent redirect, you must specify the destination URL as the first parameter and the HTTP status code as the second parameter.

    $app = new Slim();
    $app->get('/old', function () use ($app) {
        $app->redirect('/new', 301);
    });
    $app->run();

This method will automatically set the **Location:** header. The HTTP redirect response will be sent to the HTTP client immediately.