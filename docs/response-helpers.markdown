# Response Helpers [response-helpers] #

The Response object provides several instance methods to help you inspect and interact with the underlying HTTP response.

## Finalize [response-helpers-finalize] ##

The Response object's `finalize()` method returns a numeric array of status, header, and body. The status is an integer; the header is an iterable data structure; and the body is a string. Were you to create a new Response object in the Slim application or in middleware, you would call this method to produce the status, header, and body for the underlying response.

    $res = new Slim_Http_Response();
    $res->status(400);
    $res->write('You made a bad request');
    $res['Content-Type'] = 'text/plain';
    $array = $res->finalize(); //returns [200, ['Content-type' => 'text/plain'], 'You made a bad request']

## Redirect [response-helpers-redirect] ##

The Response object's `redirect()` method will help you quickly set the status and **Location:** header needed to return a **3xx Redirect** response.
    
    $app->response()->redirect('/foo', 303);

In this example, the Response will now have a **Location:** header with value "/foo" and a 303 status code.

## Status Inspection [response-helpers-inspection] ##

The Response object provides several methods to help you quickly inspect the type of Response based on its status. All return a boolean value. They are:

    //Is this an informational response?
    $app->response()->isInformational();

    //Is this a 200 OK response?
    $app->response()->isOk();

    //Is this a 2xx successful response?
    $app->response()->isSuccessful();

    //Is this a 3xx redirection response?
    $app->response()->isRedirection();

    //Is this a specific redirect response? (301, 302, 303, 307)
    $app->response()->isRedirect();

    //Is this a forbidden response?
    $app->response()->isForbidden();

    //Is this a not found response?
    $app->response()->isNotFound();

    //Is this a client error response?
    $app->response()->isClientError();

    //Is this a server error response?
    $app->response()->isServerError();