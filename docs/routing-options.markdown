# OPTIONS Routes [routing-options] #

Use the `options()` application instance method to map a callback function to a resource URI that is requested with the HTTP OPTIONS method.

    $app = new Slim();

    //For PHP 5 >= 5.3
    $app->options('/books/:id', function ($id) {
        //Provide options for this resource to the client
    });

    //For PHP 5 < 5.3
    $app->options('/books/:id', 'options_book');
    function options_book($id) {
        //Provide options for this resource to the client
    }

In this example, an HTTP OPTIONS request for "/books/1" will invoke the associated callback function.

The first argument of the `options()` application instance method is the resource URI. The last argument is anything that returns `true` for `is_callable()`. I encourage you to use PHP >= 5.3 so you may take advantage of anonymous functions.

## Method Override ##

Unfortunately, modern browsers do not provide native support for HTTP OPTIONS requests. To work around this limitation, ensure your HTML form's **method** attribute is "post", then add a method override parameter to your HTML form like this:

    <form action="/books/1" method="post">
        ... other form fields here...
        <input type="hidden" name="_METHOD" value="OPTIONS"/>
        <input type="submit" value="Get Options For Book"/>
    </form>