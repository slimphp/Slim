# The Environment [environment] #

The Slim Framework implements the [Rack protocol](http://rack.rubyforge.org/doc/files/SPEC.html), and a Slim application is a Rack application written in PHP instead of Ruby. A Slim application implements a `call()` instance method that accepts one argument — the environment — and returns an array of HTTP status, header, and body.

## What is the Environment? ##

The "environment" is an associative array of common environment settings that are parsed once and passed by reference to the Slim application and to each Slim middleware layer. You are free to modify the environment variables at any time, and any changes you make will immediately become available throughout the application.

When you instantiate a Slim application, the environment variables are parsed automatically from the server environment; you do not need to set these yourself. However, you are free to modify or supplement these variables in [Slim middleware](#middleware).

## Environment Variables ##

The following text respectfully borrows the same information originally available at <http://rack.rubyforge.org/doc/files/SPEC.html>. The environment array **must** include these variables:

REQUEST_METHOD
:   The HTTP request method. This is required and may never be an empty string.

SCRIPT_NAME
:   The initial portion of the request URI's "path" that corresponds to the physical directory in which the Slim application is installed --- so that the application knows its virtual "location". This may be an empty string if the application is installed in the top-level of the public document root directory. This will never have a trailing slash.

PATH_INFO
:   The remaining portion of the request URI's "path" that determines the "virtual" location of the HTTP request's target resource within the Slim application context. This will always have a leading slash; it may or may not have a trailing slash.

QUERY_STRING
:   The part of the HTTP request's URI after, but not including, the "?". This is required but may be an empty string.

SERVER_NAME
:   When combined with the **SCRIPT\_NAME** and **PATH\_INFO**, this can be used to create a fully qualified URL to an application resource. However, if **HTTP_HOST** is present, that should be used instead of this. This is required and may never be an empty string.

SERVER_PORT
:   When combined with the **SCRIPT\_NAME** and **PATH\_INFO**, this can be used to create a fully qualified URL to any application resource. This is required and may never be an empty string.

HTTP_*
:   Variables matching the HTTP request headers sent by the client. The existence of these variables correspond with those sent in the current HTTP request.

slim.url_scheme
:   Will be "http" or "https" depending on the HTTP request URL.

slim.input
:   Will be a string representing the raw HTTP request body. If the HTTP request body is empty (e.g. with a GET request), this will be an empty string.

slim.errors
:   Must always be a writable resource; by default, this is a write-only resource handle to **php://stderr**.

The Slim application can store its own data in the environment, too. The environment array's keys must contain at least one dot, and should be prefixed uniquely (e.g. "prefix.foo"). The prefix **slim.** is reserved for use by the Slim framework itself and must not be used otherwise. The environment must not contain the keys **HTTP\_CONTENT\_TYPE** or **HTTP\_CONTENT\_LENGTH** (use the versions without **HTTP\_**). The CGI keys (named without a period) must have String values. There are the following restrictions:

* slim.url_scheme must either be "http" or "https".
* slim.input must be a string.
* There must be a valid, writable resource in "slim.errors".
* The **REQUEST\_METHOD** must be a valid token.
* The **SCRIPT\_NAME**, if non-empty, must start with /
* The **PATH\_INFO**, if non-empty, must start with /
* The **CONTENT_LENGTH**, if given, must consist of digits only.
* One of **SCRIPT\_NAME** or **PATH\_INFO** must be set. **PATH\_INFO** should be / if **SCRIPT\_NAME** is empty. **SCRIPT\_NAME** never should be /, but instead be an empty string.