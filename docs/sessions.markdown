# Sessions [sessions] #

## PHP Sessions ##

If you prefer to use PHP's native session management, you must prepare and start the session on your own. Slim will not do this for you. If you use HTTP caching in your application, I encourage you to disable PHP's native session expiration headers since these will conflict with any ETag or Last-Modified HTTP headers that Slim will set. You can disable PHP's native session expiration headers like this:
    
    session_cache_limiter(false);
    session_start();

## Secure Sessions ##

Slim does come with optional middleware that will store session data in secure, encrypted HTTP cookies. You can use this middleware like this:

    $app = new Slim();
    $app->add('Slim_Middleware_SessionCookie', array(
        'expires' => '20 minutes',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => false,
        'name' => 'slim.session',
        'secret_key' => 'change_me'
    ));
    //... define routes here
    $app->run();

The second argument is optional; it is shown here so you can see the default middleware settings. The session cookie middleware will work seamlessly with the `$_SESSION` superglobal, so you can easily migrate to this session storage middleware with zero changes to your application code.

If you use the session cookie middleware, you **DO NOT need to start a native PHP session**. The `$_SESSION` superglobal will still be available, and it will be persisted into an HTTP cookie via the middleware layer rather than with PHP's native session management.

Remember, HTTP cookies are inherently limited to only 4 Kb of data. If your encrypted session data will exceed this length, you should instead rely on PHP's native sessions or an alternate session store.