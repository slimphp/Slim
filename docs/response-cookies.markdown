# Response Cookies [response-cookies] #

The Slim application instance provides several helper methods to send cookies with the HTTP response.

## Set Cookie ##

This example demonstrates how to use the Slim application's `setCookie()` instance method to create a Cookie that will be sent with the HTTP response:

    $app->setCookie('foo', 'bar', '2 days');

This creates a cookie with name **foo** and value **bar** that expires two days from now. You may also provide additional cookie properties, including path, domain, secure, and httponly. The Slim application's `setCookie()` method uses the same signature as PHP's native `setCookie()` function.

    $app->setCookie($name, $value, $expiresAt, $path, $domain, $secure, $httponly);

The last argument, `$httpOnly`, was added in PHP 5.2. However, because Slim's underlying cookie implementation does not rely on PHP's native `setCookie()` function, you may use the `$httpOnly` cookie property even with PHP 5.1.

## Set Encrypted Cookie [response-cookies-encrypted] ##

You may also create encrypted cookies using the Slim application's `setEncryptedCookie()` instance method. This method acts the same as the Slim application's `setCookie()` instance method demonstrated above, but it will encrypt the cookie value using the AES-256 cipher and your own secret key. To use encryption, you **must** define your encryption key when you instantiate your Slim application like this:

    $app = new Slim(array(
        'cookies.secret_key' => 'my_secret_key'
    ));

If you prefer, you may also change the default cipher and cipher mode, too:

    $app = new Slim(array(
        'cookies.secret_key' => 'my_secret_key',
        'cookies.cipher' => MCRYPT_RIJNDAEL_256,
        'cookies.cipher_mode' => MCRYPT_MODE_CBC
    ));

The encrypted cookie value is hashed and later verified to ensure data integrity so that its value is not changed while on the HTTP client.

## Delete Cookie [response-cookies-delete] ##

You can delete a cookie using the Slim application's `deleteCookie()` instance method. This will remove the cookie from the HTTP client before the next HTTP request. This method accepts the same signature as the Slim application's `setCookie()` instance method, just without the `$expires` argument. Only the first argument is required.

    $app->deleteCookie('foo');

Or if you need to also specify the **path** and **domain**:

    $app->deleteCookie('foo', '/', 'foo.com');

You may also further specify the **secure** and **httponly** properties, too:

    $app->deleteCookie('foo', '/', 'foo.com', true, true);