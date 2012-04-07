# View Data [views-data] #

The view's `setData()` and `appendData()` instance methods inject data into the view object; the injected data is available to view templates. View data is stored internally as a key-value array.

## Set Data ##

The view's `setData()` instance method will _overwrite_ existing view data.

You may use this method to set a single variable to a given value like this:

    $view = $app->view();
    $view->setData('color', 'red');

The view's data will now contain a key "color" with value "red". You may also use the view's `setData()` instance method to batch assign an entire array of data like this:

    $view = $app->view();
    $view->setData(array(
        'color' => 'red',
        'size' => 'medium'
    ));

Remember, the view's `setData()` instance will _replace_ all previous data.

## Append Data ##

The view also has the `appendData()` instance method. This method _appends_ data to the view's existing data. This method accepts an array as its one and only argument like this:

    $app = new Slim();
    $app->view()->appendData(array(
        'foo' => 'bar'
    ));