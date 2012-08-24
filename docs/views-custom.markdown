# Custom Views [views-custom] #

A custom view is a subclass of `Slim_View` that implements a public `render()` method. When the custom view's render method is invoked, it is passed the desired template pathname (relative to the app's "templates.path" setting) as its one and only argument.

    class CustomView extends Slim_View {
        public function render( $template ) {
            return 'The final rendered template';
        }
    }

The custom view can do whatever it wants, so long as it returns the template's rendered output as a string. A custom view makes it easy to integrate popular PHP template systems like Twig or Smarty.

You can browse ready-to-use custom views that work with popular PHP template engines in the [Slim-Extras](https://github.com/codeguy/Slim-Extras) repository on GitHub.

The custom view may access data passed to it by the Slim application's `render()` instance method. The custom view can access this array of data with `$this->data`. Here is an example.

## The Route

    $app = new Slim();
    $app->get('/books/:id', function ($id) use ($app) {
        $app->render('show.php', array('title' => 'Sahara'));
    });

## The View

    class CustomView extends Slim_View {
        public function render( $template ) {
            //$template === 'show.php'
            //$this->data['title'] === 'Sahara'
        }
    }

If the custom view is not discoverable by a registered autoloader, it must be required before the Slim application is instantiated.

    require 'Slim/Slim.php';
    require 'CustomView.php';
    $app = new Slim(array(
        'view' => 'CustomView'
    ));

It is also possible to pass a custom view instance into the Slim application's constructor instead of the custom view class name. This may be helpful if your custom view requires special preparation before application instantiation.

    require 'Slim/Slim.php';
    require 'CustomView.php';
    $app = new Slim(array(
        'view' => new CustomView()
    ));