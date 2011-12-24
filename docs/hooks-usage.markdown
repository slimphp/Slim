# How to use a hook #

You can assign a callable to a hook using the Slim application's `hook()` instance method like this:

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

Hooks do not pass arguments to their callables. If a callable needs to access the Slim application, you can use these methods instead:

    $app = new Slim();

    //Use a PHP 5.3+ anonymous function and the use keyword
    $app->hook('the.hook.name', function () use ($app) {
        // Do something
    });

    //If using PHP less than 5.3, use the global keyword
    $app->hook('the.hook.name', 'nameOfMyHookFunction');
    function nameOfMyHookFunction() {
        global $app;
        //Do something
    }