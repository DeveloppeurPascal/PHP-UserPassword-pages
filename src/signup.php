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
	require_once(__DIR__."/inc/config.inc.php");

	// This page is only available when no user is connected.
	if (hasCurrentUser()) {
		header("location: index.php");
		exit;
	}
	
	define("CSignupForm", 1);
	define("CSignupWait", 2);
	define("CSignupOk", 3);
	
	$SignupStatus = CSignupForm;
	
	$error = false;
	$error_message = "";
	$DefaultField = "User";

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
				$DefaultField = "Password";
			}
			else {
				$password2 = isset($_POST["password2"])?trim(strip_tags($_POST["password2"])):"";
				if (empty($password2)) {
					$error = true;
					$error_message .= "Fill your second password to register.\n";
					$DefaultField = "Password2";
				}
				else if ($password != $password2) {
					$error = true;
					$error_message .= "The two password fields must contain the same thing.\n";
					$DefaultField = "Password";
				}
				else {
					$db = getPDOConnection();
					if (! is_object($db)) {
						$error = true;
						$error_message .= "Database access error. Contact the administrator.\n";
						$SignupStatus = CSignupForm;
					}
					else {
						$qry = $db->prepare("select id, comp from users where email=:email limit 0,1");
						$qry->execute(array(":email" => $email));
						if (false === ($rec = $qry->fetch(PDO::FETCH_OBJ))) {
							$qry = $db->prepare("insert into users (email, password, pwd_salt, enabled, create_ip, create_datetime, comp) values (:e,:pwd,:s,0,:ci,:cdt,:comp)");
							$pwd_salt = getNewIdString(mt_rand(5,25));
							$activation_code = getNewIdString(25);
							$activation_url = SITE_URL."signup.php?a=".$activation_code."&k=".substr(md5(SIGNUP_SALT.$activation_code.$pwd_salt.$email),7,10)."&e=".urlencode($email);
							$comp = "";
							setUserCompValue($comp, "act_code", $activation_code);
							setUserCompValue($comp, "act_exp", time()+60*60); // Now + 1 hour (60s * 60m)
							setUserCompValue($comp, "act_url", $activation_url);
							$qry->execute(array(":e" => $email, ":pwd" => getEncryptedPassword($password, $pwd_salt), ":s" => $pwd_salt, ":ci" => $_SERVER["REMOTE_ADDR"], ":cdt" => date("YmdHis"), ":comp" => $comp));
							if (_DEBUG) 
							{
								mail($email, "Please activate your email", "Hi\n\nPlease click on this link to activate your email :\n".$activation_url."\n\nBest regards\n\nThe team");
							}
							else {
								// TODO : replace this by an email check link
								die("Sending an activation email is not available here.");
							}
							$SignupStatus = CSignupWait;
						}
						else {
							$error = true;
							$error_message .= "User already exists.\n";
							if (_DEBUG) 
							{
								$activation_url = getUserCompValue($rec->comp, "act_url");
								if (false !== $activation_url) {
									// TODO : tester expiration du lien, le regénérer s'il est expiré
									mail($email, "Please activate your email", "Hi\n\nPlease click on this link to activate your email :\n".$activation_url."\n\nBest regards\n\nThe team");
									$error_message .= "An activation link has been resend to you by email.\n";
								}
								else {
									// TODO : erreur - pas d'url d'activation alors que compte non activé
								}
							}
							else {
								// TODO : replace enabled by an email check link
								die("Sending an activation email is not available here.");
							}
						}
					}
				}
			}
		}
	}
	else {
		// sample activation URL : 
		// http://localhost/PHPUserForm/src/signup.php?a=zFEWi5EheiyqJyznxJFke8awI&k=2c0013a7e9&e=pprem%40pprem.net
		
		$activation_code = isset($_GET["a"])?trim($_GET["a"]):false;
		if ((false !== $activation_code) && (! empty($activation_code))) {
			$key = isset($_GET["k"])?trim($_GET["k"]):false;
			if ((false !== $key) && (! empty($key))) {
				$email = isset($_GET["e"])?trim($_GET["e"]):false;
				if ((false !== $email) && (! empty($email))) {
					$db = getPDOConnection();
					if (is_object($db)) {
						$qry = $db->prepare("select id, pwd_salt, comp from users where email=:email and enabled=0 limit 0,1");
						$qry->execute(array(":email" => $email));
						if (false !== ($rec = $qry->fetch(PDO::FETCH_OBJ))) {
							$activation_code = getUserCompValue($rec->comp, "act_code");
							if ($key == substr(md5(SIGNUP_SALT.$activation_code.$rec->pwd_salt.$email),7,10)) {
								$activation_expiration = getUserCompValue($rec->comp, "act_exp");
								if ($activation_expiration <= time()) {
									// activation code expired
									$SignupStatus = CSignupForm;
								}
								else {
									unsetUserCompKey($rec->comp, "act_code");
									unsetUserCompKey($rec->comp, "act_exp");
									unsetUserCompKey($rec->comp, "act_url");
									$qry = $db->prepare("update users set enabled=1, email_checked=1, email_check_ip=:ip, email_check_datetime=:dt, comp=:comp where id=:id");
									$qry->execute(array(":ip" => $_SERVER["REMOTE_ADDR"], ":dt" => date("YmdHis"), ":comp" => $rec->comp, ":id" => $rec->id));
									$SignupStatus = CSignupOk;
								}
							}
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
	
	switch ($SignupStatus) {
		case CSignupForm:
?><form method="POST" action="signup.php" onSubmit="return ValidForm();"><input type="hidden" name="frm" value="1">
	<p>
		<label for="User">User email</label><br>
		<input id="User" name="user" type="email" value="<?php print(isset($email)?htmlspecialchars($email):""); ?>" prompt="Your email address">
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
<script>
	document.getElementById('<?php print($DefaultField); ?>').focus();
	function ValidForm() {
		email = document.getElementById('User');
		if (0 == email.value.length) {
			email.focus();
			window.alert('Your email address is needed !');
			return false;
		}
		pwd = document.getElementById('Password');
		if (0 == pwd.value.length) {
			pwd.focus();
			window.alert('New password needed !');
			return false;
		}
		pwd2 = document.getElementById('Password2');
		if (0 == pwd2.value.length) {
			pwd2.focus();
			window.alert('New password needed !');
			return false;
		}
		if (pwd.value != pwd2.value) {
			pwd.value = '';
			pwd2.value = '';
			pwd.focus();
			window.alert('Values are different, please rewrite them !');
			return false;
		}
		return true;
	}
</script>
<p><a href="login.php">Log in</a></p><?php
			break;
		case CSignupWait:
?><p>We sent an activation email to your address. Please click on it.</p>
<p>Of course check your spams if you didn't see it in your inbox.</p><?php
			break;
		case CSignupOk:
?><p>Welcome new user. Please <a href="login.php">log in</a> to use the service.</p><?php
			break;
		default :
	}
 include_once(__DIR__."/inc/footer.inc.php"); ?></body>
</html>