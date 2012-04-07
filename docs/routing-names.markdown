# Named Routes [routing-names] #

Slim lets you assign a name to a route. Naming a route enables you to dynamically generate URLs using the [urlFor](#routing-helpers-urlfor) helper method. When you use the `urlFor()` application instance method to create application URLs, you can freely change route patterns without breaking your application. Here is an example of a named route:

    $app = new Slim();
    $app->get('/hello/:name', function ($name) {
        echo "Hello, $name!";
    })->name('hello');

You may now generate URLs for this route using the `urlFor()` application instance method, described later in this documentation. The route `name()` method is also chainable:

    $app = new Slim();
    $app->get('/hello/:name', function ($name) {
        echo "Hello, $name!";
    })->name('hello')->conditions(array('name' => '\w+'));