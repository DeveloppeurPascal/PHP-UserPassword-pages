<?php
	// PHP User Password Basics
	// (c) Patrick Prémartin
	//
	// Distributed under license AGPL.
	//
	// Infos and updates :
	// https://github.com/DeveloppeurPascal/PHP-UserPassword-Basics
	
	session_start();
	require_once(__DIR__."/inc/functions.inc.php");

	// This page is only available when no user is connected.
	if (hasCurrentUser()) {
		header("location: index.php");
		exit;
	}
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
		<title>Lost Password - PHP User Password Basics</title>
		<style>
			.error {
				color: red;
				background-color: yellow;
			}
		</style>
	</head>
	<body><?php include_once(__DIR__."/inc/header.inc.php"); ?>
		<h2>Lost password</h2>
		<form method="POST" action="lostpassword.php">
			<p>
				<label for="User">User email</label><br>
				<input id="User" name="user" type="email" value="" prompt="Your email address">
			</p>
			<p>
				<button type="submit">Renew password</button>
			</p>
		</form>
		<p><a href="login.php">Log in</a></p>
		<p><a href="signup.php">Sign up</a></p>
<?php include_once(__DIR__."/inc/footer.inc.php"); ?></body>
</html>