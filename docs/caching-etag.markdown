# Etag [caching-etag] #

A Slim application provides built-in support for HTTP caching using ETags. An ETag is a unique identifier for a resource URI. When an ETag header is set with the `etag()` method, the HTTP client will send an **If-None-Match** header with each subsequent HTTP request of the same resource URI. If the ETag value for the resource URI matches the **If-None-Match** HTTP request header, the Slim application will return a **304 Not Modified** HTTP response that will prompt the HTTP client to continue using its cache; this also prevents the Slim application from serving the entire markup for the resource URI, saving bandwidth and response time.

Setting an ETag with Slim is very simple. Invoke the `etag()` application method in your route callback, passing it a unique ID as the first and only argument.

    $app->get('/foo', function () use ($app) {
        $app->etag('unique-id');
        echo "This will be cached after the initial request!";
    });

That's it. Make sure the unique ETag ID *is unique for the given resource*. Also make sure the ETag unique ID changes as your resource changes; otherwise, the HTTP client will continue serving its outdated cache.