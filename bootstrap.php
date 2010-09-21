<?php

require('slim/Slim.php');

/* INIT SLIM */

Slim::init();

/* CALLBACKS */

Slim::before(function () {
	Slim::response()->write('Before!');
});

Slim::after(function () {
	Slim::response()->write('After!');
});

/* ROUTES */

Slim::get('/', function () {
	Slim::render('index.php');
});

Slim::post('/post', function () {
	echo '<br/><br/>Here are the details about your POST request:<br/><br/>';
	print_r(Slim::request());
});

Slim::put('/put', function () {
	echo '<br/><br/>Here are the details about your PUT request:<br/><br/>';
	print_r(Slim::request());
});

Slim::delete('/delete', function () {
	echo '<br/><br/>Here are the details about your DELETE request:<br/><br/>';
	print_r(Slim::request());
});

?>