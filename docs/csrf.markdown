# CSRF Protection [csrf] #

A Slim application provides build-in protection from [CSRF](http://en.wikipedia.org/wiki/Cross-site_request_forgery) attacks.
It's disabled by default. To enable protection just set the option:

    $app = new Slim(array(
        'csrf.check' => true,
        'csrf.field' => 'csrf_token', // Default value
    ));

We recommend you use it in your applications.