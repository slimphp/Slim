# Custom Views

The Slim Framework provides a default View class that uses PHP template files by default. This folder includes custom View classes that you may use with alternative template libraries, such as [Twig](http://www.twig-project.org/), [Smarty](http://www.smarty.net/), or [Mustache](http://mustache.github.com/).

## TwigView

The `TwigView` custom View class provides support for the [Twig](http://www.twig-project.org/) template library. You can use the TwigView custom View in your Slim application like this:

	<?php
	require 'slim/Slim.php';
	require 'views/TwigView.php';
	Slim::init('TwigView');
	//Insert your application routes here
	Slim::run();
	?>
	
You will need to configure the `TwigView::$twigOptions` and `TwigView::$twigDirectory` class variables before using the TwigView class in your application. These variables can be found at the top of the `views/TwigView.php` class definition.