# URL Rewriting [routing-indepth-with-rewrite] #

I strongly encourage you to use a web server that supports URL rewriting; this will let you enjoy clean, human-friendly URLs with your Slim application. To enable URL rewriting, you should use the appropriate tools provided by your web server to forward all HTTP requests to the PHP file in which you instantiate and run your Slim application.  The following are sample, bare minimum, configurations for Apache with mod_php and nginx.  These are not meant to be production ready configurations but should be enough to get you up and running.  To read more on server deployment in general you can continue reading [phptherightway.com](http://www.phptherightway.com/#servers_and_deployment).

## Apache and mod_rewrite [apache-mod-rewrite]##

Here is an example directory structure:

    /path/www.mysite.com/
        public_html/ <-- Document root!
            .htaccess
            index.php <-- I instantiate Slim here!
        lib/
            Slim/ <-- I store Slim lib files here!

The **.htaccess** file in the directory structure above contains:

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [QSA,L]

You also need a directory directive to enable **.htaccess** files and allow the **RewriteEngine** directive to be used.  This is generally setup in your configuration in the form of:
    <Directory "/path/www.mysite.com/public_html">
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

As a result, Apache will send all requests for non-existent files to my **index.php** script in which I instantiate and run my Slim application. With URL rewriting enabled and assuming the following Slim application is defined in **index.php**, you can access the application route below at "/foo" rather than "/index.php/foo".

    $app = new Slim();
    $app->get('/foo', function () {
        echo "Foo!";
    });
    $app->run();

## nginx [nginx-tryfiles]##

We will use the same example directory structure as before, but with nginx our configuration will go into **nginx.conf**.

    /path/www.mysite.com/
        public_html/ <-- Document root!
            index.php <-- I instantiate Slim here!
        lib/
            Slim/ <-- I store Slim lib files here!

Here is a snippet of a **nginx.conf** in which we use the **try_files** directive to serve the file if it exists, good for static files (images, css, js etc), and otherwise forward it on to the **index.php** file.

    server {
        listen       80;
        server_name  www.mysite.com mysite.com;
        root         /path/www.mysite.com/public_html;

        try_files $uri /index.php;

        # this will only pass index.php to the fastcgi process which is generally safer but
        # assumes the whole site is run via Slim.
        location /index.php {
            fastcgi_connect_timeout 3s;     # default of 60s is just too long
            fastcgi_read_timeout 10s;       # default of 60s is just too long
            include fastcgi_params;
            fastcgi_pass 127.0.0.1:9000;    # assumes you are running php-fpm locally on port 9000
        }
    }

Most installations will have a default **fastcgi_params** file setup that you can just include as shown above.  Some configurations don't include the **SCRIPT_FILENAME** parameter.  You must ensure you include this parameter otherwise you might end up with a **No input file specified** error from the fastcgi process.  This can be done directly in the **location** block or simply added to the **fastcgi_params** file.  Either way it looks like this:
    fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;