<?php

require('slim/Slim.php');

Slim::init();

Slim::before(function () {
	Slim::response()->write('Before!');
});

Slim::after(function () {
	Slim::response()->write('After!');
});

Slim::get('/', function () {
	echo 'Welcome to Slim';
});

Slim::get('/books/:one/:two', function ($one, $two) {
	Slim::render('test.php');
});

?>