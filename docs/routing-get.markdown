# GET Routes [routing-get] #

Use the `get()` application instance method to map a callback function to a resource URI that is requested with the HTTP GET method.

    $app = new Slim();
    
    //For PHP >= 5.3
    $app->get('/books/:id', function ($id) {
        //Show book identified by $id
    });
    
    //For PHP < 5.3
    $app->get('/books/:id', 'show_book');
    function show_book($id) {
        //Show book identified by $id
    }

In this example, an HTTP GET request for "/books/1" will invoke the associated callback function, passing "1" as the callback function argument.

The first argument of the `get()` application instance method is the resource URI. The last argument is anything that returns `true` for `is_callable()`. I encourage you to use PHP >= 5.3 so you may take advantage of anonymous functions.