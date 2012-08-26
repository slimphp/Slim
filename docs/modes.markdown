# Application Modes [application-modes]

It is common practice to run web applications in a specific mode depending on the current state of the project. If you are developing the application, you will run the application in "development" mode; if you are testing the application, you will run the application in "test" mode; if you launch the application, you will run the application in "production" mode.

Slim supports the concept of modes in that you may define your own modes and prompt Slim to prepare itself appropriately in the current mode. For example, you may want to enable debugging in "development" mode but not in "production" mode. The examples below demonstrate how to configure Slim differently for a given mode.

## What is a mode? [what-is-a-mode]

Technically, an application mode is merely a string of text - like "development" or "production" - that has an associated callback function used to prepare the Slim application appropriately. The application mode may be anything you like: "testing", "production", "development", or even "foo".

## How do I set the Slim application mode? [how-to-set-mode]

### Use an environment variable

If Slim sees an environment variable named **SLIM_MODE**, it will set the current application mode to that variable's value. This lets you define the Slim application mode programmatically if calling the application from the command line.

    $_ENV['SLIM_MODE'] = 'production';

### Use an application setting

If an environment variable is not found, Slim will next look for the mode in the application settings.

    $app = new Slim(array(
        'mode' => 'production'
    ));

### Default mode

If the environment variable and application setting are not found, Slim will set the application mode to "development".

## Configure Slim for a specific mode [configure-for-mode]

After you instantiate a Slim application, you may configure the Slim application for a specific mode with the `configureMode()` application instance method. This method accepts two arguments: the first is the name of the target mode, and the second is anything that returns `true` for `is_callable()` that will be immediately invoked if the first argument matches the current application mode.

In this example, assume the current application mode is "production". Only the callable associated with the "production" mode will be invoked. The callable associated with the "development" mode will be ignored until the application mode is changed to "development".

    $app = new Slim(array(
        'mode' => 'production'
    ));

    $app->configureMode('production', function () use ($app) {
        $app->config(array(
            'log.enable' => true,
            'debug' => false
        ));
    });

    $app->configureMode('development', function () use ($app) {
        $app->config(array(
            'log.enable' => false,
            'debug' => true
        ));
    });