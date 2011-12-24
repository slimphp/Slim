# The Request Object [request] #

Each Slim application instance has one Request object. The Request object is a high-level interface that allows you to more easily interact with the [Environment](#environment) variables for the current HTTP request. Although each Slim application includes a default Request object, the `Slim_Http_Request` class is idempotent; you may instantiate the class at will (in [Middleware](#middleware) or elsewhere in your Slim application) without affecting the application as a whole.

You can obtain a reference to the Slim application's Request object like this:

    $app->request();