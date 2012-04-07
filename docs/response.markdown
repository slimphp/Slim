# The Response Object [response] #

Each Slim application instance has one Response object. The Response object is a high-level interface that allows you to easily interact with the HTTP response that is returned to the HTTP client. Although each Slim application includes a default Response object, the `Slim_Http_Response` class is idempotent; you may instantiate the class at will (in [Middleware](#middleware) or elsewhere in your Slim application) without affecting the application as a whole.

You can obtain a reference to the Slim application's Response object like this:

    $app->response();

An HTTP response has three primary properties: the status code, the header, and the body. The Response object provides several helper methods, described next, that help you interact with these HTTP response properties in your Slim application. The default Response object in each Slim application will return a **200 OK** HTTP response with the **text/html** content type.