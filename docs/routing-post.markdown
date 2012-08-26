# POST Routes [routing-post] #

Use the `post()` application instance method to map a callback function to a resource URI that is requested with the HTTP POST method.

    $app = new Slim();

    //For PHP >= 5.3
    $app->post('/books', function () {
        //Create book
    });

    //For PHP < 5.3
    $app->post('/books', 'post_book');
    function post_book() {
        //Create book
    }

In this example, an HTTP POST request for "/books" will invoke the associated callback function.

The first argument of the `post()` application instance method is the resource URI. The last argument is anything that returns `true` for `is_callable()`. I encourage you to use PHP >= 5.3 so you may take advantage of anonymous functions.