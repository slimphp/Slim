# Views [views] #

A View is a PHP class that **returns** a rendered template as a string.

## Set the View ##

The Slim application View may be set during instantiation like this:

    //During instantiation as instance
    $app = new Slim(array(
        'view' => new TwigView()
    ));

    //During instantiation as class name
    $app = new Slim(array(
        'view' => 'TwigView'
    ));

During instantiation, you may pass either an instance of the View or the class name; if you use the class name, the class must be discoverable (either already included or available to a registered autoloader).

The View may be set during runtime, too, like this:

    $app = new Slim();
    $app->get('/foo', function () use ($app) {
        //Using an instance of View
        $app->view(new TwigView());
        
        //Using a class name
        $app->view('TwigView');
    });
    $app->run();

If you set the View after instantiation, data already set on the previous View will be transferred to the new View.

## Get the View ##

You can just as easily fetch a reference to the current View with the same method but without an argument:

    $theView = $app->view();

## The Default View ##

The Slim application will use a default View unless you specify your own. The default view uses `require()` and `extract()` to pull a template into memory and render its content against a set of variables. The rendered content is captured in an output buffer and returned. It's a fairly naive implementation, but it is easily overridden with [custom Views](#views-custom).