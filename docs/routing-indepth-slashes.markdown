# Trailing Slashes [routing-indepth-slashes] #

Slim routes automatically provide pretty URLs and intelligent redirection - behavior very similar to the Apache web server. Here are two example routes:

    $app = new Slim();

    //This has a trailing slash
    $app->get('/services/', function () {});

    //This does not have a trailing slash
    $app->get('/contact', function () {});
    
At first glance, both routes appear similar. However, in the first route example, the canonical URL for the services route has a trailing slash. It acts the same as a folder; accessing it without a trailing slash will prompt Slim to redirect to the canonical URL with the trailing slash.

In the second example, the URL is defined without a trailing slash. Therefore, it behaves similar to a file. Accessing it with a trailing slash will result with a **404 Not Found** response.

This behavior allows URLs to continue working if users access the page and forget the trailing slash. This is consistent with the Apache web server's behavior. Because Slim automatically redirects URLs, search engines will always index the canonical URLs rather than index both the correct and incorrect URLs.