# Route Parameters [routing-parameters] #

You can embed parameters into route resource URIs. In this example, I have two parameters in my route URI, ":one" and ":two".

    $app = new Slim();

    //For PHP >= 5.3
    $app->get('/books/:one/:two', function ($one, $two) {
        echo "The first paramter is " . $one;
        echo "The second parameter is " . $two;
    });

    //For PHP < 5.3
    $app->get('/books/:one/:two', 'callback_name');
    function callback_name($one, $two) {
        echo "The first paramter is " . $one;
        echo "The second parameter is " . $two;
    }

To create a URL parameter, prepend ":" to the parameter name in the route URI pattern. When the route matches the current HTTP request, the values for each route parameter are extracted from the HTTP request URI and are passed into the associated callback function in order of appearance.

## Wildcard Route Parameters

You may also use wildcard route parameters. These will capture one or many URI segments that correspond
to the route pattern's wildcard parameter into an array. A wildcard parameter is identified by a "+" suffix; it
otherwise acts the same as normal route parameters shown above. Here's an example:

    $app = new Slim();
    $app->get('/hello/:name+', function ($name) {
        // Do something
    });

When you invoke this example application with a resource URI "/hello/Josh/T/Lockhart", the route callback's `$name` argument
will be equal to `array('Josh', 'T', Lockhart')`.

## Optional Route Parameters

You may also have optional route parameters. These are ideal for using one route for a blog archive. To declare optional route parameters, specify your route pattern like this:

    $app = new Slim();
    $app->get('/archive(/:year(/:month(/:day)))', function ($year = 2010, $month = 12, $day = 05) {
        echo sprintf('%s-%s-%s', $year, $month, $day);
    });

Each subsequent route segment is optional. This route will accept HTTP requests for:

    /archive
    /archive/2010
    /archive/2010/12
    /archive/2010/12/05

If an optional route segment is omitted from the HTTP request, the default values in the callback signature are used instead.

Currently, you can only use optional route segments in situations like the example above where each route segment is subsequently optional. You may find this feature unstable when used in scenarios different from the example above.
