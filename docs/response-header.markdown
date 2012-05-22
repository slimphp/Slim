# Response Header [response-header] #

The HTTP response returned to the HTTP client will have a header. The HTTP header is a list of keys and values that provide metadata about the HTTP response. You can use the Slim application's Response object to set the HTTP response's header. The Response object is special because it acts like an array. Here's an example.

    $response = $app->response();
    $response['Content-Type'] = 'application/json';
    $response['X-Powered-By'] = 'Slim';

Just the same, you can also fetch headers from the Response object like this:

    $response = $app->response();
    $contentType = $response['Content-Type'];
    $poweredBy = $response['X-Powered-By'];

If a header with the given name does not exist, `NULL` is returned instead. You may specify header names with upper, lower, or mixed case with dashes or underscores. Use the naming convention with which you are most comfortable.