# Request Method [request-method] #

Every HTTP request has a method (e.g. GET or POST). You can obtain the current HTTP request method via the Slim application's Request object:

    //What is the request method?
    $method = $app->request()->getMethod(); //returns "GET", "POST", etc.

    //Is this a GET request?
    $app->request()->isGet(); //true or false

    //Is this a POST request?
    $app->request()->isPost(); //true or false

    //Is this a PUT request?
    $app->request()->isPut(); //true or false

    //Is this a DELETE request?
    $app->request()->isDelete(); //true or false

    //Is this a HEAD request?
    $app->request()->isHead(); //true or false

    //Is this a OPTIONS request?
    $app->request()->isOptions(); //true or false

    //Is this a request made with Ajax/XHR?
    $app->request()->isAjax(); //true or false