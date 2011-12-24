# Request Parameters [request-parameters] #

An HTTP request may have associated parameters (not to be confused with [Route parameters](#routing-paramters)). The GET, POST, or PUT parameters sent with the current HTTP request are exposed via the Slim application's Request object.

If you want to quickly fetch a request parameter value without considering its type, use the `params()` Request method:

    $paramValue = $app->request()->params('paramName');

The `params()` Request instance method will first search **PUT** parameters, then **POST** parameters, then **GET** parameters. If no parameter is found, `NULL` is returned. If you only want to search for a specific type of parameter, you can use these Request instance methods instead:

    //GET parameter
    $paramValue = $app->request()->get('paramName');

    //POST parameter
    $paramValue = $app->request()->post('paramName');

    //PUT parameter
    $paramValue = $app->request()->put('paramName');

If a parameter does not exist, each method above will return `NULL`. You can also invoke any of these functions without an argument to obtain an array of all parameters of the given type:

    $allGetParams = $app->request()->get();
    $allPostParams = $app->request()->post();
    $allPutParams = $app->request()->put();