# Middleware [middleware] #

The Slim Framework implements the Rack protocol, and a Slim application is Rack application written in PHP instead of Ruby. Because Slim adopts the Rack protocol, you can add any number of middleware to a Slim application.

Middleware is a layer surrounding a Slim application that may inspect, analyze, or modify the Environment and HTTP response before and/or after the Slim application (or downstream middleware) is invoked.