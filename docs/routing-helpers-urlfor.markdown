# URL For [routing-helpers-urlfor] #

The `urlFor()` application instance method lets you dynamically create URLs *for a named route* so that, were a route pattern to change, your URLs would update automatically without breaking your application. This example demonstrates how to generate URLs for a named route.

    $app = new Slim();
    
    //Create a named route
    $app->get('/hello/:name', function ($name) use ($app) {
        echo "Hello $name";
    })->name('hello');
    
    //Generate a URL for the named route
    $url = $app->urlFor('hello', array('name' => 'Josh'));

In this example, `$url` is "/hello/Josh". To use the `urlFor()` application instance method, you must first assign a name to a route. Next, invoke the `urlFor()` application instance method. The first argument is the name of the route, and the second argument is an associative array used to replace the route's URL parameters with actual values; the array's keys must match parameters in the route's URI and the values will be used as substitutions.