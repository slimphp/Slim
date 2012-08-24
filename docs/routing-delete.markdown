# DELETE Routes [routing-delete] #

Use the `delete()` application instance method to map a callback function to a resource URI that is requested with the HTTP DELETE method.

    $app = new Slim();

    //For PHP >= 5.3
    $app->delete('/books/:id', function ($id) {
        //Delete book identified by $id
    });

    //For PHP < 5.3
    $app->delete('/books/:id', 'delete_book');
    function delete_book($id) {
        //Delete book identified by $id
    }

In this example, an HTTP DELETE request for "/books/1" will invoke the associated callback function.

The first argument of the `delete()` application instance method is the resource URI. The last argument is anything that returns `true` for `is_callable()`. I encourage you to use PHP >= 5.3 so you may take advantage of anonymous functions.

## Method Override ##

Unfortunately, modern browsers do not provide native support for HTTP DELETE requests. To work around this limitation, ensure your HTML form's **method** attribute is "post", then add a method override parameter to your HTML form like this:

    <form action="/books/1" method="post">
        ... other form fields here...
        <input type="hidden" name="_METHOD" value="DELETE"/>
        <input type="submit" value="Delete Book"/>
    </form>

If you are using [Backbone.js](http://documentcloud.github.com/backbone/) or a command-line HTTP client, you may also override the HTTP method by using the `X-HTTP-Method-Override` header.