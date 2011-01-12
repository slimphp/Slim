<?php include 'templates/header.php'; ?>

		<h1>Welcome to Slim!</h1>
		<p>
			Congratulations! Your Slim application is running. If this is 
			your first time using Slim, start with this <a href="http://www.slimframework.com/learn" target="_blank">"Hello World" Tutorial</a>.
		</p>
		
		<section>
			<h2>Getting Started</h2>
			<p>Here's some information about this application.</p>
			<ol>
				<li>The application code is in <code>bootstrap.php</code></li>
				<li>The application templates are in <code>templates/</code></li>
				<li>Custom Views for Twig, Smarty, and Mustache are in <code>views/</code></li>
			</ol>
		</section>
		
		<section>
			<h2>RESTful Routes</h2>
			<p>Click the buttons below to test Slim's RESTful routing.</p>
			<div style="margin-left: 1.5em; font-size: 12px">
				<h3>Sample POST Route</h3>
				<p style="margin: 0">Click this button to view an example POST route</p>
				<form action="post" method="post">
					<input type="hidden" name="foo" value="bar"/>
					<input type="submit" value="Send POST"/>
				</form>

				<h3>Sample PUT Route</h3>
				<p style="margin: 0">Click this button to view an example PUT route</p>
				<form action="put" method="post">
					<input type="hidden" name="foo" value="bar"/>
					<input type="hidden" name="_METHOD" value="PUT"/>
					<input type="submit" value="Send PUT"/>
				</form>

				<h3>Sample DELETE Route</h3>
				<p style="margin: 0">Click this button to view an example DELETE route</p>
				<form action="delete" method="post">
					<input type="hidden" name="foo" value="bar"/>
					<input type="hidden" name="_METHOD" value="DELETE"/>
					<input type="submit" value="Send DELETE"/>
				</form>
			</div>
		</section>
		
		<section>
			<h2>Browse the online documentation</h2>
			<p>Use these links to learn more about the Slim PHP 5 framework.</p>
			<ul style="margin-bottom: 0">
				<li>Browse the <a href="https://github.com/codeguy/Slim/wiki/Slim-Framework-Documentation" target="_blank">stable version docs</a></li>
				<li>Browse the <a href="https://github.com/codeguy/Slim/wiki/Documentation" target="_blank">develop version docs</a></li>
				<li>Follow <a href="http://www.twitter.com/slimphp" target="_blank">@slimphp</a> on Twitter</li>
			</ul>
		</section>

<?php include 'templates/footer.php'; ?>