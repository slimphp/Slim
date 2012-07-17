# Sessions [sessions] #

## Native PHP Sessions ##

A Slim application does not presume anything about sessions. If you prefer to use a PHP session, you must configure and start a native PHP session with `session_start()` before you instantiate the Slim application.

You should also disable PHP's session cache limiter so that PHP does not send conflicting cache expiration headers with the HTTP response. You can disable PHP's session cache limiter with:

    session_cache_limiter(false);
    session_start();

## Secure Sessions ##

You may also use the `Slim_Middleware_SessionCookie` middleware to persist session data in encrypted, hashed HTTP cookies. To enable the session cookie middleware, add the `Slim_Middleware_SessionCookie` middleware to your Slim application like this:

    $app = new Slim();

    $app->add(new Slim_Middleware_SessionCookie(array(
        'expires' => '20 minutes',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => false,
        'name' => 'slim_session',
        'secret' => 'CHANGE_ME',
        'cipher' => MCRYPT_RIJNDAEL_256,
        'cipher_mode' => MCRYPT_MODE_CBC
    )));

    // Define routes here

    $app->run();

The second argument is optional; it is shown here so you can see the default middleware settings. The session cookie middleware will work seamlessly with the `$_SESSION` superglobal so you can easily migrate to this session storage middleware with zero changes to your application code.

If you use the session cookie middleware, you **DO NOT need to start a native PHP session**. The `$_SESSION` superglobal will still be available, and it will be persisted into an HTTP cookie via the middleware layer rather than with PHP's native session management.

Remember, HTTP cookies are inherently limited to only 4 kilobytes of data. If your encrypted session data will exceed this length, you should instead rely on PHP's native sessions or an alternate session store.