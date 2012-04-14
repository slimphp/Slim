# Without URL Rewriting [routing-indepth-without-rewrite] #

Slim will work without URL rewriting. In this scenario, you must include the name of the PHP file in which you instantiate and run the Slim application in the resource URI. For example, assume the following Slim application is defined in **index.php** at the top level of your virtual host's document root:

    $app = new Slim();
    $app->get('/foo', function () {
        echo "Foo!";
    });
    $app->run();

You can access the defined route at "/index.php/foo". If the same application is instead defined in **index.php** inside of the physical subdirectory **blog/**, you can access the defined route at **/blog/index.php/foo**.