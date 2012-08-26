# Request Paths [request-paths] #

Every HTTP request received by a Slim application will have a root URI and a resource URI.

The **root URI** is the physical URL path of the directory in which the Slim application is instantiated and run. If a Slim application is instantiated in **index.php** within the top-most directory of the virtual host's document root, the root URI will be an empty string. If a Slim application is instantiated and run in **index.php** within a physical subdirectory of the virtual host's document root, the root URI will be the path to that subdirectory *with* a leading slash and *without* a trailing slash.

The **resource URI** is the virtual URI path of an application resource. The resource URI will be matched to the Slim application's routes.

Here's an example. Assume the Slim application is installed in a physical subdirectory **/foo** beneath your virtual host's document root. Also assume the full HTTP request URL (what you'd see in the browser location bar) is **/foo/books/1**. The root URI is **/foo** (the path to the physical directory in which the Slim application is instantiated) and the resource URI is **/books/1** (the path to the application resource).

You can get the Request's root URI and resource URI like this:

    $app = new Slim();

    //Get the Request root URI
    $rootUri = $app->request()->getRootUri();

    //Get the Request resource URI
    $resourceUri = $app->request()->getResourceUri();