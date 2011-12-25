# URL Rewriting [routing-indepth-with-rewrite] #

I strongly encourage you to use a web server that supports URL rewriting; this will let you enjoy clean, human-friendly URLs with your Slim application. To enable URL rewriting, you should use the appropriate tools provided by your web server to forward all HTTP requests to the PHP file in which you instantiate and run your Slim application.

I am most familiar with the Apache web server, so my examples below demonstrate how to setup a Slim application with Apache and mod_rewrite.

Here is an example directory structure:

    www.mysite.com/
        public_html/ <-- Document root!
            .htaccess
            index.php <-- I instantiate Slim here!
        lib/
            Slim/ <-- I store Slim lib files here!

The **.htaccess** file in the directory structure above contains:

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]

As a result, Apache will send all requests for non-existent files to my **index.php** script in which I instantiate and run my Slim application. With URL rewriting enabled and assuming the following Slim application is defined in **index.php**, you can access the application route below at "/foo" rather than "/index.php/foo".

    $app = new Slim();
    $app->get('/foo', function () {
        echo "Foo!";
    });
    $app->run();

This process will act very much the same for nginx, except that you will define the URL rewriting in your nginx configuration file rather than in a **.htaccess** file. I will defer to those more intelligent than me to demonstrate nginx configuration.