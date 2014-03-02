<?php
header('HTTP/1.1 503 Service Temporarily Unavailable');
date_default_timezone_set( 'UTC' );
header('Retry-After: ' . date( 'r', time() + 300 ));
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
<meta charset="UTF-8" />
<title>Moved to a new server - bradt.ca</title>

<style>
body {
	margin: 0;
	padding: 25px 35px;
}

h1 {
	margin-top: 0;
}
</style>

</head>

<body>

<h1>Moved to a new server</h1>

<p>
	We've moved the site to a new server.
	If you're seeing this, bradt.ca is still pointing
	at our old server for you.
</p>
<p>
	<strong>Please try again in 5 minutes.</strong> If it's still not working,
	try shutting down and re-opening your web browser.
</p>
<p>
	<em>503 Service Temporarily Unavailable</em>
</p>

</body>

</html>