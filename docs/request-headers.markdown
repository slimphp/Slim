# Request Headers [request-headers] #

A Slim application will automatically parse all HTTP request headers. You can access the Request headers using the Request `headers()` instance method.

    $app = new Slim();

    //Fetch all request headers as associative array
    $headers = $app->request()->headers();

    //Fetch only the ACCEPT_CHARSET header
    $charset = $app->request()->headers('ACCEPT_CHARSET'); //returns string or NULL

In the second example, the `headers()` method will either return a string value or `NULL` if the header with the given name does not exist.

The HTTP specification states that HTTP header names may be uppercase, lowercase, or mixed-case. Slim is smart enough to parse and return header values whether you request a header value using upper, lower, or mixed case header name, with either underscores or dashes. So use the naming convention with which you are most comfortable.