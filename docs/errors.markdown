# Error Handling [errors] #

Let's face it: sometimes things go wrong. It is important to intercept errors and respond to them appropriately. A Slim application provides helper methods to respond to errors and exceptions.

Each Slim application handles its own errors and exceptions. If there are multiple Slim applications in the same PHP script, each application will catch and handle its own errors and exceptions. Errors or exceptions generated outside of a Slim application must be caught and handled elsewhere.

To prevent recoverable errors from stopping the entire PHP script, Slim converts errors into `ErrorException` instances that are caught and handled by a default or custom Slim application error handler.