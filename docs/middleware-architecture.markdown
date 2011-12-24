# Middleware Architecture [middleware-architecture] #

Think of a Slim application as the core of an onion. Like ogres, onions have layers. Each layer of the onion is a middleware. When you invoke the Slim application's `run()` method, the outer-most middleware layer is invoked first. When ready, that middleware is responsible for invoking the next middleware layer (or Slim application) that it surrounds. This process steps deeper into the onion — through each middleware layer — until the core Slim application is invoked.

This stepped application flow is possible because each middleware and the Slim application all implement a common interface - the `call()` instance method - that accepts a reference to the Environment settings as its one and only argument and returns an array of HTTP status, HTTP header, and HTTP body.

When you add a new middleware, it will become a new outer layer and surround the previous outer-most middleware (if available) or the Slim application itself. Middleware are invoked from the outside in.