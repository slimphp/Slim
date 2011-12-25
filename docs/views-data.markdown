# View Data [views-data] #

To pass data into a View for use in templates, you can use the View object's `setData()` or `appendData()` instance methods.

## Set Data ##

The View object's `setData()` instance method will overwrite existing data. You may use this method to set a single variable to a given value like this:

    $view = $app->view();
    $view->setData('color', 'red');

The View's data array will now contain a key "color" with value "red". You may also use the `setData()` to batch assign an entire array of data like this:

    $view = $app->view();
    $view->setData(array(
        'color' => 'red',
        'size' => 'medium'
    ));

Remember, when you use the `setData()` method to batch assign an array of data, all previous data in the View will be removed and replaced with the new array of data.

## Append Data ##

The View object also provides a `appendData()` instance method so that you may append information to the View's existing data. This method only accepts an array as its one required argument.

    $app = new Slim();
    $app->view()->appendData(array('foo' => 'bar'));