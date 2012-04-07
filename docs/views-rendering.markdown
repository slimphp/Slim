# Rendering [views-rendering] #

You can use the Slim application's `render()` instance method to ask the current View to render a template with a given set of variables. The `render()` method will `echo()` the output returned from the View so that the output is appended automatically to the Response object's body. This assumes nothing about how the template is rendered; that is delegated to the View.

    $app = new Slim();

    //For PHP >= 5.3
    $app->get('/books/:id', function ($id) use ($app) {
        $app->render('myTemplate.php', array('id' => $id));
    });

    //For PHP < 5.3
    $app->get('/books/:id', 'show_book');
    function show_book($id) {
        $app = Slim::getInstance();
        $app->render('myTemplate.php', array('id' => $id));
    }

If you need to pass data from the route callback into the View, you must explicitly do so by passing an array as the second argument of the Slim application's `render()` instance method like this:

    $app->render(
        'myTemplate.php',
        array( 'name' => 'Josh' )
    );

You can also set the HTTP response status when you render a template:

    $app->render(
        'myTemplate.php',
        array( 'name' => 'Josh' ), 
        404
    );