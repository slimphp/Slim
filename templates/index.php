<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>Slim micro-framework for PHP5</title>
		<style>
			body{margin:0 auto;padding:0;width:600px;font:12px/20px Helvetica,Arial,Verdana,sans-serif;color:#666;}
			h1,h2,h3{color:#000;}
		</style>
	</head>
	<body>

		<h1>Welcome to Slim!</h1>
		<p>
			Congratulations! You are running on Slim, a RESTful micro-framework for PHP5.
			Use the forms below to test Slim's RESTful routing. You can edit this template 
			in <code>templates/index.php</code>. Learn more about Slim at:
		</p>
		<ul>
			<li><a href="http://github.com/codeguy/Slim/">View Slim's source code on GitHub</a></li>
			<li><a href="http://slim.joshlockhart.com/">Read Slim's documentation</a></li>
		</ul>
		<p>
			Slim is created and maintained by <a href="http://www.joshlockhart.com">Josh Lockhart</a>.
			Slim is released under the <a href="http://www.opensource.org/licenses/mit-license.php">MIT public license</a>.
		</p>

		<h2>Send POST Request</h2>
		<form action="post" method="post">
			<input type="hidden" name="foo" value="bar"/>
			<input type="submit" value="Send POST"/>
		</form>

		<h2>Send PUT Request</h2>
		<form action="put" method="post">
			<input type="hidden" name="foo" value="bar"/>
			<input type="hidden" name="_METHOD" value="PUT"/>
			<input type="submit" value="Send PUT"/>
		</form>

		<h2>Send DELETE Request</h2>
		<form action="delete" method="post">
			<input type="hidden" name="foo" value="bar"/>
			<input type="hidden" name="_METHOD" value="DELETE"/>
			<input type="submit" value="Send DELETE"/>
		</form>

	</body>
</html>
