# Stop [routing-helpers-stop] #

The `stop()` application instance method will stop the Slim application and send the current HTTP response to the client as is. No *ifs*, *ands*, or *buts*.

    $app = new Slim();
    $app->get('/foo', function () use ($app) {
        echo "You will see this...";
        $app->stop();
        echo "But not this";
    });
    $app->run();