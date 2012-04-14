# Instantiation

First `require()` Slim into your application file. If you move the **Slim/** directory elsewhere on your filesystem, it is important that you keep Slim's dependencies in the same directory as Slim.php. Keeping the files together enables you to only require **Slim.php** and have the other files loaded automatically. Assuming the **Slim/** directory is on your include path, you only need to call:

    require 'Slim/Slim.php';

After you require Slim, instantiate your Slim application like this:

    $app = new Slim();

The Slim constructor accepts an optional associative array of settings to customize the Slim application during instantiation (see [settings]).