# Routing In-Depth [routing-indepth] #

Slim's routing methods, described earlier, help you map a resource URI to a callback function for a given HTTP method (or for multiple HTTP methods). This is the simplest explanation of Slim's router. However, there is more than meets the eye.

When you invoke a routing method (e.g `get()`, `post()`, `put()`, etc.) to define a route, you are actually telling Slim to create a Route object that responds to the respective HTTP request method. The Route object will know its resource URI, its callback, and the HTTP methods to which it responds. You may further assign [names](#routing-names) and [conditions](#routing-conditions) to a Route object.

When you invoke your app's `run()` method, Slim will determine the current HTTP request's method and URI. Slim next iterates over each route in your application in the order they were defined. If a route's URI matches the HTTP request's URI, Slim asks the matching route if it answers to the current HTTP request's method. If yes, the route's callback is invoked; if no, the Route is ignored but Slim remembers the HTTP methods to which that Route *does* respond (more on this in a bit).

If a route matches both the current HTTP request's URI and method, Slim will invoke that Route's callback and send the eventual HTTP response to the client. Subsequent route objects will not be iterated.

If Slim finds routes that match the HTTP request URI but not the HTTP request method, Slim will return a **405 Method Not Allowed** response with an **Allow:** header that lists the HTTP methods to which the found routes do respond (as I alluded to above).

If Slim does not find routes that match the HTTP request URI, Slim will return a **404 Not Found** response.