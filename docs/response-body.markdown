# Response Body [response-body] #

The HTTP response returned to the client will have a body. The HTTP body is the actual content of the HTTP response delivered to the client. You can use the Slim application's Response object to set the HTTP response's body like this:

    $response = $app->response();
    $response->body('Foo'); //The body is now "Foo" (overwrites)
    $response->write('Bar'); //The body is now "FooBar" (appends)

When you overwrite or append the Response's body, the Response object will automatically set the `Content-Length` header based on the byte size of the new response body.

You can fetch the Response object's body using the same Response `body()` instance method without an argument like this:

    $response = $app->response();
    $body = $response->body();

Usually, you will never need to manually set the Response body with the Response `body()` or `write()` methods; instead, the Slim application will do this for you. Whenever you `echo()` content from within a route callback, the `echo()`'d content is captured in an output buffer and later appended to the Response body before the HTTP response is returned to the client.