# Request Cookies [request-cookies] #

## Get Cookies [request-cookies-basic] ##

A Slim application will automatically parse cookies sent with the current HTTP request. You can fetch cookie values with the Slim application's `getCookie()` instance method like this:

    $foo = $app->getCookie('foo');

Only Cookies **sent** with the current HTTP request are accessible with this method. If you set a cookie during the current request, it will not be accessible with this method until the subsequent request. If you want to fetch an array of all cookies sent with the current request, you must use the Request object's `cookies()` instance method like this:

    //Get all cookies as associative array
    $cookies = $app->request()->cookies();

When multiple cookies with the same name are available (e.g. because they have different paths) only the most specific one (longest matching path) is returned. See RFC 2109.

## Get Encrypted Cookies [request-cookies-encrypted] ##

If you previously set an encrypted cookie, you can fetch its decrypted value with the Slim application's `getEncryptedCookie()` instance method like this:

    $cookieValue = $app->getEncryptedCookie('foo');

If the cookie was modified while with the HTTP client, Slim will automatically destroy the cookie's value and invalidate the cookie in the next HTTP response. You can disable this behavior by passing `false` as the second argument:

    $cookieValue = $app->getEncryptedCookie('foo', false);

Whether you destroy invalid cookies or not, `NULL` is returned if the cookie does not exist or is invalid.
