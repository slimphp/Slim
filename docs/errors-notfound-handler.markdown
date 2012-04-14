# Not Found Handler [not-found-handler] #

It is an inevitability that someone will request a page that does not exist. The Slim application lets you easily define a custom **Not Found** handler with the Slim application's `notFound()` instance method. The Not Found handler will be invoked when a matching route is not found for the current HTTP request. This method may be invoked in two different contexts.

## When defining the handler ##

If you invoke the Slim application's `notFound()` instance method and specify a callable object as its first and only argument, this method will register the callable object as the Not Found handler. However, the registered handler will not be invoked.

    $app = new Slim();

    //For PHP >= 5.3
    $app->notFound(function () use ($app) {
        $app->render('404.html');
    });

    //For PHP < 5.3
    $app->notFound('custom_not_found_callback');
    function custom_not_found_callback() {
        $app = Slim::getInstance();
        $app->render('404.html');
    }

## When invoking the Not Found handler ##

If you invoke the Slim application's `notFound()` instance method without any arguments, this method will invoke the previously registered Not Found handler.

    $app = new Slim();

    $app->get('/hello/:name', function ($name) use ($app) {
        if ( $name === 'Waldo' ) {
            $app->notFound();
        } else {
            echo "Hello, $name";
        }
    });