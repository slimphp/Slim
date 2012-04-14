# Pass [routing-helpers-pass] #

A route can tell the Slim application to continue to the next matching route with the `pass()` application instance method. When this method is invoked, the Slim application will immediately stop processing the current matching route and invoke the next matching route. If no subsequent matching route is found, a **404 Not Found** response is sent to the client. Here is an example. Assume an HTTP request for "GET /hello/Frank".

    $app = new Slim();
    $app->get('/hello/Frank', function () use ($app) {
        echo "You won't see this...";
        $app->pass();
    });
    $app->get('/hello/:name', function ($name) use ($app) {
        echo "But you will see this!";
    });
    $app->run();