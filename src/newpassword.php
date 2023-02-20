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

	// This page is only available when a user is connected.
	if (! hasCurrentUser()) {
		header("location: index.php");
		exit;
	}
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
		<title>Choose a password - PHP User Password Basics</title>
		<style>
			.error {
				color: red;
				background-color: yellow;
			}
		</style>
	</head>
	<body><?php include_once(__DIR__."/inc/header.inc.php"); ?>
		<h2>Choose a password</h2>
		<form method="POST" action="newpassword.php">
			<p>
				<label for="User">User email</label><br>
				<input id="User" name="user" type="email" value="<?php print(htmlspecialchars(getCurrentUserEmail())); ?>" readonly="readonly">
			</p>
			<p>
				<label for="Password">Password</label><br>
				<input id="Password" name="password" type="password" value="" prompt="Your password">
			</p>
			<p>
				<label for="Password2">Password (rewrite the same)</label><br>
				<input id="Password2" name="password2" type="password" value="" prompt="Your password">
			</p>
			<p>
				<button type="submit">Set password</button>
			</p>
		</form>
<?php include_once(__DIR__."/inc/footer.inc.php"); ?></body>
</html>