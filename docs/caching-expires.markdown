# Expires [caching-expires] #

Used in conjunction with the Slim application's `etag()` or `lastModified()` methods, the `expires()` method sets an `Expires:` header on the HTTP response informing the HTTP client when its client-side cache for the current resource should be considered stale. The HTTP client will continue serving from its client-side cache until the expiration date is reached, at which time the HTTP client will send a conditional GET request to the Slim application.

The `expires()` method accepts one argument: an integer UNIX timestamp, or a string to be parsed with `strtotime()`.

    $app->get('/foo', function () use ($app) {
        $app->etag('unique-resource-id');
        $app->expires('+1 week');
        echo "This will be cached client-side for one week";
    });