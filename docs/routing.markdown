# Routing [routing]

Slim helps you map resource URIs to callback functions for specific HTTP request methods (e.g. GET, POST, PUT, DELETE, OPTIONS or HEAD). Slim will invoke the first route that matches the current HTTP request's URI and method.

If Slim does not find routes with URIs that match the HTTP request URI, Slim will automatically return a **404 Not Found** response.

If Slim finds routes with URIs that match the HTTP request URI *but not* the HTTP request method, Slim will automatically return a **405 Method Not Allowed** response with an **Allow:** header whose value lists HTTP methods that are acceptable for the requested resource.