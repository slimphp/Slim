# Application Names and Scopes [names-and-scopes]

When you build a Slim application you will enter various scopes in your code (e.g. global scope and function scope). You will likely need a reference to your Slim application in each scope. You can use application names, or `use` with PHP >= 5.3, to obtain a reference to your Slim application.

## Application Names [application-names]

Every Slim application may be given a name. **This is optional**. Names help you get a reference to a Slim application instance in any scope throughout your code. Here is how you set and get an application's name:

    $app = new Slim();
    $app->setName('foo');
    $theName = $app->getName(); //returns "foo"

## Scope Resolution [scope-resolution]

So how do you get a reference to your Slim application? The example below demonstrates how to obtain a reference to a Slim application within a route callback function. The Slim `$app` variable is accessed in the global scope to define the GET route. We also need to access the Slim `$app` variable within the route's callback scope to render a template.

    $app = new Slim();
    $app->get('/foo', function () {
        $app->render('foo.php'); //<--ERROR
    });

This fails because we cannot access the Slim `$app` variable inside of the route callback function. In PHP >= 5.3 we can inject the Slim `$app` variable into the anonymous function scope with the `use` keyword:

    $app = new Slim();
    $app->get('/foo', function () use ($app) {
        $app->render('foo.php'); //<--SUCCESS
    });

Now it works correctly. In PHP < 5.3, you can use `Slim::getInstance()` instead.

    $app = new Slim();
    $app->get('/foo', 'foo');
    function foo() {
        $app = Slim::getInstance();
        $app->render('foo.php');
    }

The first instantiated Slim application is automatically assigned the name "default". If you invoke `Slim::getInstance()` without an argument, it will return the Slim application that is named "default".

If you instantiate multiple Slim applications with PHP < 5.3, it is important that you assign each Slim application a name.

    $app1 = new Slim();
    $app1->setName('myApp1');
    
    $app2 = new Slim();
    $app2->setName('myApp2');
    
    $app1->get('/foo', 'appOneCallback');
    function appOneCallback() {
        $app = Slim::getInstance('myApp1');
    }

    $app2->get('/foo', 'appTwoCallback');
    function appTwoCallback() {
        $app = Slim::getInstance('myApp2');
    }

Invoking `Slim::getInstance()` without an argument will return a reference to `$app1` because that is the first Slim application instantiated.