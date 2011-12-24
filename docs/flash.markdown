# Flash Messaging [flash] #

Slim supports Flash messaging much like Rails and other larger web frameworks. Flash messaging allows you to define messages that will persist until the next HTTP request but no further. This is helpful to display messages to the user after a given event or error occurs.

As shown below, the Slim application's `flash()` and `flashNow()` instance methods accept two arguments: a key and a message. The key may be whatever you want and defines how the message will be accessed in the View templates. For example, if I invoke the Slim application's `flash('foo', 'The foo message')` instance method with those arguments, I can access that message in the next request’s templates with `flash['foo']`.

Flash messages are persisted with sessions. By default, Flash messages are stored in `$\_SESSION['flash']`; you can change the Flash message `$_SESSION` key during Slim instantiation like this:

    $app = new Slim(array(
        'session.flash_key' => 'new_key'
    ));

## Flash

The Slim application's `flash()` instance method sets a message that will be available in the next request’s View templates. The message in this example will be available in the variable `flash['error']` in the next request’s View templates.

    $app->flash('error', 'User email is required');

## Flash Now

The Slim application's `flashNow()` instance method sets a message that will be available in the current request’s View templates. Messages set with the `flashNow()` application instance method will not be available in the next request. The message in the example below will be available in the variable `flash['info']` in the current request’s View templates.

    $app->flashNow('info', 'Your credit card is expired');

## Flash Keep

This method tells the Slim application to keep existing Flash messages set in the previous request so they will be available to the next request. This method is helpful for persisting Flash messages across HTTP redirects.

    $app->flashKeep();