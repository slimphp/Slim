# Last-Modified [caching-last-modified] #

A Slim application provides built-in support for HTTP caching using the resource's last modified date. When you specify a last modified date, Slim tells the HTTP client the date and time the current resource was last modified. The HTTP client will then send a **If-Modified-Since** header with each subsequent HTTP request for the given resource URI. If the last modification date you specify matches the **If-Modified-Since** HTTP request header, the Slim application will return a **304 Not Modified** HTTP response that will prompt the HTTP client to use its cache; this also prevents the Slim application from serving the entire markup for the resource URI saving bandwidth and response time.

Setting a last modified date with Slim is very simple. You only need to invoke the `lastModified()` application instance method in your route callback passing in a UNIX timestamp that represents the last modification date for the given resource. Be sure the `lastModified()` application instance method's timestamp updates along with the resource's last modification date; otherwise, the browser client will continue serving its outdated cache.

    $app->get('/foo', function () use ($app) {
        $app->lastModified(1286139652);
        echo "This will be cached after the initial request!";
    });