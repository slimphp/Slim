# PUT Routes [routing-put] #

Use the `put()` application instance method to map a callback function to a resource URI that is requested with the HTTP PUT method.

    $app = new Slim();

    //For PHP >= 5.3
    $app->put('/books/:id', function ($id) {
        //Update book identified by $id
    });

    //For PHP < 5.3
    $app->put('/books/:id', 'put_book');
    function put_book($id) {
        //Update book identified by $id
    }

In this example, an HTTP PUT request for "/books/1" will invoke the associated callback function.

The first argument of the `put()` application instance method is the resource URI. The last argument is anything that returns `true` for `is_callable()`. I encourage you to use PHP >= 5.3 so you may take advantage of anonymous functions.

## Method Override ##

Unfortunately, modern browsers do not provide native support for HTTP PUT requests. To work around this limitation, ensure your HTML form's **method** attribute is "post", then add a method override parameter to your HTML form like this:

    <form action="/books/1" method="post">
        ... other form fields here...
        <input type="hidden" name="_METHOD" value="PUT"/>
        <input type="submit" value="Update Book"/>
    </form>

If you are using [Backbone.js](http://documentcloud.github.com/backbone/) or a command-line HTTP client, you may also override the HTTP method by using the `X-HTTP-Method-Override` header.