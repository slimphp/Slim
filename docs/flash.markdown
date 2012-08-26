# Flash Messaging [flash] #

Slim supports flash messaging much like Rails and other larger web frameworks. Flash messaging allows you to define messages that will persist until the next HTTP request but no further. This is helpful to display messages to the user after a given event or error occurs.

As shown below, the Slim application's `flash()` and `flashNow()` instance methods accept two arguments: a key and a message. The key may be whatever you want and defines how the message will be accessed in the View templates. For example, if I invoke the Slim application's `flash('foo', 'The foo message')` instance method with those arguments, I can access that message in the next request's templates with `flash['foo']`.

Flash messages are persisted with sessions; sessions are required for flash messages to work. Flash messages are stored in `$_SESSION['slim.flash']`.

## Flash

The Slim application's `flash()` instance method sets a message that will be available in the next request's view templates. The message in this example will be available in the variable `flash['error']` in the next request's view templates.

    $app->flash('error', 'User email is required');

## Flash Now

The Slim application's `flashNow()` instance method sets a message that will be available in the current request's view templates. Messages set with the `flashNow()` application instance method will not be available in the next request. The message in the example below will be available in the variable `flash['info']` in the current request's view templates.

    $app->flashNow('info', 'Your credit card is expired');

## Flash Keep

This method tells the Slim application to keep existing flash messages set in the previous request so they will be available to the next request. This method is helpful for persisting flash messages across HTTP redirects.

    $app->flashKeep();