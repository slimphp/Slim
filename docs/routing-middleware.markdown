# Route Middleware [routing-middleware] #

Slim enables you to associate middleware with a specific application route. When the given route matches the current HTTP request and is invoked, Slim will first invoke the associated middleware in the order they are defined.

## What is Route Middleware?

Route middleware is anything that returns true for is_callable.

## How do I Add Route Middleware?

When you define a new application route with the Slim application's `get()`, `post()`, `put()`, or `delete()` methods you must define a route pattern and a callable to be invoked when the route matches an HTTP request.

    $app = new Slim();
    $app->get('/foo', function () {
        //Do something
    });

In the example above, the first argument is the route pattern. The last argument is the callable to be invoked when the route matches the current HTTP request. The route pattern must always be the first argument. The route callable must always be the last argument.

You can assign middleware to this route by passing each middleware as a separate interior or... (ahem) middle... argument like this:

    function myMiddleware1() {
        echo "This is middleware!";
    }
    function myMiddleware2() {
        echo "This is middleware!";
    }
    $app = new Slim();
    $app->get('/foo', 'myMiddleware', 'myMiddleware2', function () {
        //Do something
    });

When the **/foo** route matches the current HTTP request, the `myMiddleware1` and `myMiddleware2` functions will be invoked in sequence before the route's callable.

If you are running PHP >= 5.3, you can get a bit more creative. Suppose you wanted to authenticate the current user against a given role for a specific route. You could use some closure magic like this:

    $authenticateForRole = function ( $role = 'member' ) {
        return function () use ( $role ) {
            $user = User::fetchFromDatabaseSomehow();
            if ( $user->belongsToRole($role) === false ) {
                $app = Slim::getInstance();
                $app->flash('error', 'Login required');
                $app->redirect('/login');
            }
        };
    };
    $app = new Slim();
    $app->get('/foo', $authenticateForRole('admin'), function () {
        //Display admin control panel
    });

## Are there any arguments passed to the Route Middleware callable?

Yes.  The middleware callable is called with one argument, the currently matched `Slim_Route` object.

    $aBitOfInfo = function (Slim_Route $route) {
        echo "Current route is " . $route->getName();
    };

    $app->get('/foo', $aBitOfInfo, function () {
        echo "foo";
    });
