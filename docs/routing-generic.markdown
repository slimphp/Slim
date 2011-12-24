# Generic Routes [routing-generic] #

Slim provides the `map()` application instance method to define generic routes that are not immediately associated with an HTTP method.

    $app = new Slim();
    $app->map('/generic/route', function () {
        echo "I'm a generic route!";
    });
    $app->run();

This example always returns a **404 Not Found** response because the "/generic/route" route does not respond to any HTTP methods. Use the [via()](#routing-custom) method (available on Route objects), to assign one or many HTTP methods to a generic route.