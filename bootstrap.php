<?php

/*** REQUIRE SLIM ***/

require 'slim/Slim.php';


/*** INITIALIZE SLIM ***/

Slim::init();


/*** CALLBACKS ***/

//Register a "before" callback for PHP >=5.3
Slim::before(function () {
	Slim::response()->write('<p>Before!</p>');
});

//Register a "before" callback for PHP <5.3
/*
Slim::before('example_before');
function example_before() {
	Slim::response()->write('Before!');
}
*/

//Register an "after" callback for PHP >=5.3
Slim::after(function () {
	Slim::response()->write('<p>After!</p>');
});

//Register an "after" callback for PHP <5.3
/*
Slim::after('example_after');
function example_after() {
	Slim::response()->write('After!');
}
*/


/*** ROUTES ***/

//Sample GET route for PHP >=5.3
Slim::get('/', function () {
	Slim::render('index.php');
});

//Sample GET route for PHP <5.3
/*
Slim::get('/', 'get_example');
function get_example() {
	Slim::render('index.php');
}
*/

//Sample POST route for PHP >=5.3
Slim::post('/post', function () {
	echo '<p>Here are the details about your POST request:</p>';
	print_r(Slim::request());
});

//Sample POST route for PHP <5.3
/*
Slim::post('/post', 'post_example');
function post_example() {
	echo '<br/><br/>Here are the details about your POST request:<br/><br/>';
	print_r(Slim::request());
}
*/

//Sample PUT route for PHP >=5.3
Slim::put('/put', function () {
	echo '<p>Here are the details about your PUT request:</p>';
	print_r(Slim::request());
});

//Sample PUT route for PHP <5.3
/*
Slim::put('/put', 'put_example');
function put_example() {
	echo '<br/><br/>Here are the details about your PUT request:<br/><br/>';
	print_r(Slim::request());
}
*/

//Sample DELETE route for PHP >=5.3
Slim::delete('/delete', function () {
	echo '<p>Here are the details about your DELETE request:</p>';
	print_r(Slim::request());
});

//Sample DELETE route for PHP <5.3
/*
Slim::delete('/delete', 'delete_example');
function delete_example() {
	echo '<br/><br/>Here are the details about your DELETE request:<br/><br/>';
	print_r(Slim::request());
}
*/


/*** NAMED ROUTES *****/

Slim::get('/hello/:name', function ($name) {
	echo "<p>Hello, $name!</p>";
	echo "<p>This route using name \"Bob\" instead of \"$name\" would be: " . Slim::urlFor('hello', array('name' => 'Bob')) . '</p>';
})->name('hello')->conditions(array('name' => '\w+'));


/*** RUN SLIM ***/

Slim::run();

?>