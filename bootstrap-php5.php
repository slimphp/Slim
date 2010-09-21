<?php

require('slim/Slim.php');

/* INIT SLIM */

Slim::init();

/* CALLBACKS */

Slim::before('example_before');
function example_before() {
	Slim::response()->write('Before!');
}

Slim::after('example_after');
function example_after() {
	Slim::response()->write('After!');
}

/* ROUTES */

Slim::get('/', 'get_example');
function get_example() {
	Slim::render('index.php');
}

Slim::post('/post', 'post_example');
function post_example() {
	echo '<br/><br/>Here are the details about your POST request:<br/><br/>';
	print_r(Slim::request());
}

Slim::put('/put', 'put_example');
function put_example() {
	echo '<br/><br/>Here are the details about your PUT request:<br/><br/>';
	print_r(Slim::request());
}

Slim::delete('/delete', 'delete_example');
function delete_example() {
	echo '<br/><br/>Here are the details about your DELETE request:<br/><br/>';
	print_r(Slim::request());
}
?>
