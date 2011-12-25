# Custom Views [views-custom] #

A custom View is a PHP class that subclasses `Slim_View` and implements one method — `render()`. The custom View’s render method is passed the name of the template as its one and only argument.

    class CustomView extends Slim_View {
        public function render( $template ) {
            return 'The final rendered template';
        }
    }

The custom View can do whatever it wants, so long as it ultimately returns the template’s rendered output. A custom View makes it easy to integrate popular PHP template systems, like Twig or Smarty.

See the [Slim-Extras](https://github.com/codeguy/Slim-Extras) repository on GitHub for many different custom Views for different PHP template languages.

The custom View class will have access to any data passed to it by the Slim application's `render()` instance method. The custom View can access this data array with `$this->data`. Here is an example.

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

To use your custom View, you must `require()` the custom View class before you initialize Slim. Then you must tell Slim to use your custom View.

    require 'Slim/Slim.php';
    require 'CustomView.php';
    $app = new Slim(array(
        'view' => 'CustomView'
    ));

It is also possible to pass a custom View instance into the Slim app’s constructor instead of the custom View class name. This may be helpful if your custom View requires special preparation before application instantiation.

    require 'Slim/Slim.php';
    require 'CustomView.php';
    $app = new Slim(array(
        'view' => new CustomView()
    ));