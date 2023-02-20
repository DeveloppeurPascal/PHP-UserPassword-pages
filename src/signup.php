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
	
	$error = false;
	$error_message = "";

	if (isset($_POST["frm"]) && ("1" == $_POST["frm"])) {
		$email = isset($_POST["user"])?trim(strip_tags($_POST["user"])):"";
		if (empty($email)) {
			$error = true;
			$error_message .= "Fill your user email address to register.\n";
		}
		else {
			$password = isset($_POST["password"])?trim(strip_tags($_POST["password"])):"";
			if (empty($password)) {
				$error = true;
				$error_message .= "Fill your password to register.\n";
			}
			else {
				$password2 = isset($_POST["password2"])?trim(strip_tags($_POST["password2"])):"";
				if (empty($password2)) {
					$error = true;
					$error_message .= "Fill your second password to register.\n";
				}
				else if ($password != $password2) {
					$error = true;
					$error_message .= "The two password fields must contain the same thing.\n";
				}
				else {
					$db = getPDOConnection();
					if (! is_object($db)) {
						$error = true;
						$error_message .= "Database access error. Contact the administrator.\n";
					}
					else {
						$qry = $db->prepare("select id, email, password, pwd_salt, enabled from users where email=:email limit 0,1");
						$qry->execute(array(":email" => $email));
						if (false === ($rec = $qry->fetch(PDO::FETCH_OBJ))) {
							$qry = $db->prepare("insert into users (email, password, pwd_salt, enabled, create_ip, create_datetime) values (:e,:pwd,:s,1,:ci,:cdt)");
							// TODO : replace enabled by an email check link
							$pwd_salt = getNewIdString(mt_rand(5,25));
							$qry->execute(array(":e" => $email, ":pwd" => getEncryptedPassword($password, $pwd_salt), ":s" => $pwd_salt, ":ci" => $_SERVER["REMOTE_ADDR"], ":cdt" => date("YmdHis")));
							header("location: signup-ok.php");
							exit;
						}
						else {
							$error = true;
							$error_message .= "User already exists.\n";
							// TODO : resend activation email depending on the user state
						}
					}
				}
			}
		}
	}
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
		<title>Sign up - PHP User Password Basics</title>
		<style>
			.error {
				color: red;
				background-color: yellow;
			}
		</style>
	</head>
	<body><?php include_once(__DIR__."/inc/header.inc.php"); ?>
		<h2>Sign up</h2><?php
	if ($error && (! empty($error_message))) {
		print("<p class=\"error\">".nl2br($error_message)."</p>");
	}
?><form method="POST" action="signup.php"><input type="hidden" name="frm" value="1">
			<p>
				<label for="User">User email</label><br>
				<input id="User" name="user" type="email" value="" prompt="Your email address">
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
				<button type="submit">Register</button>
			</p>
		</form>
		<p><a href="login.php">Log in</a></p>
<?php include_once(__DIR__."/inc/footer.inc.php"); ?></body>
</html>