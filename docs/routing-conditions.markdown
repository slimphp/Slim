# Route Conditions [routing-conditions] #

Slim lets you assign conditions to route parameters. If the specified conditions are not met, the route is not run. For example, if you need a route with a second segment that must be a valid 4-digit year, you could enforce this condition like this:

    $app = new Slim();
    $app->get('/archive/:year', function ($year) {
        echo "You are viewing archives from $year";
    })->conditions(array('year' => '(19|20)\d\d'));

Invoke the `conditions()` Route method. The first and only argument is an associative array with keys that match any of the route's parameters and values that are regular expressions.

## Application-wide Route Conditions

If many of your Slim application Routes accept the same parameters and use the same conditions, you can define default application-wide Route conditions like this:

    Slim_Route::setDefaultConditions(array(
        'firstName' => '[a-zA-Z]{3,}'
    ));

Define application-wide route conditions before you define application routes. When you define a route, the route will automatically be assigned any application-wide Route conditions defined with `Slim\_Route::setDefaultConditions()`. If for whatever reason you need to get the application-wide default route conditions, you can fetch them with `Slim_Route::getDefaultConditions()`; this static method returns an array exactly as the default route conditions were defined.

You may override a default route condition by redefining the route's condition when you define the route, like this:

    $app = new Slim();
    $app->get('/hello/:firstName', $callable)->conditions(array('firstName' => '[a-z]{10,}'));

You may append new conditions to a given route like this:

    $app = new Slim();
    $app->get('/hello/:firstName/:lastName', $callable)->conditions(array('lastName' => '[a-z]{10,}'));
