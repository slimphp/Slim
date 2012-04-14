# How to use a hook #

A callable is assigned to a hook using the Slim application's `hook()` instance method:

    $app = new Slim();
    $app->hook('the.hook.name', function () {
        //Do something
    });

The first argument is the hook name, and the second argument is the callable. Each hook maintains a priority list of registered callables. By default, each callable assigned to a hook is given a priority of 10. You can give your callable a different priority by passing an integer as the third parameter of the `hook()` method like this:

    $app = new Slim();
    $app->hook('the.hook.name', function () {
        //Do something
    }, 5);

The example above assigns a priority of 5 to the callable. When the hook is called, it will sort all callables assigned to it by priority (ascending). A callable with priority 1 will be invoked before a callable with priority 10.

Hooks do not pass arguments to their callables. If a callable needs to access the Slim application, you can inject the application into the callback with the `use` keyword or with the static `getInstance()` method:

    $app = new Slim();

    //If using PHP >= 5.3
    $app->hook('the.hook.name', function () use ($app) {
        // Do something
    });

    //If using PHP < 5.3
    $app->hook('the.hook.name', 'nameOfMyHookFunction');
    function nameOfMyHookFunction() {
        $app = Slim::getInstance();
        //Do something
    }