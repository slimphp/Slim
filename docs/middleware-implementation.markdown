# Middleware Implementation [middleware-implementation] #

Middleware **must** extend the `Slim_Middleware` class and implement a public `call()` instance method. The `call()` method does not accept arguments. Otherwise, each middleware may implement its own constructor, properties, and methods. I encourage you to look at Slim's built-in middleware for working examples (e.g. `Slim/Middleware/ContentTypes.php` or `Slim/Middleware/SessionCookie.php`).

This example is the most simple implementation of Slim application middleware. It extends `Slim_Middleware`, implements a public `call()` method, and calls the next inner middleware.

    class My_Middleware extends Slim_Middleware {
        public function call() {
            $this->next->call();
        }
    }