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

	define("CLostPasswordForm", 1);
	define("CLostPasswordWait", 2);
	define("CLostPasswordChange", 3);
	define("CLostPasswordOk", 4);
	
	$LostPasswordStatus = CLostPasswordForm;

	$error = false;
	$error_message = "";
	$DefaultField = "User";

	if (isset($_POST["frm"]) && ("1" == $_POST["frm"])) { // check email
		$email = isset($_POST["user"])?trim(strip_tags($_POST["user"])):"";
		if (empty($email)) {
			$error = true;
			$error_message .= "Fill your user email address to get a new password.\n";
		}
		else {
			$db = getPDOConnection();
			if (! is_object($db)) {
				$error = true;
				$error_message .= "Database access error. Contact the administrator.\n";
			}
			else {
				$qry = $db->prepare("select id, pwd_salt, enabled, comp from users where email=:email limit 0,1");
				$qry->execute(array(":email" => $email));
				if (false === ($rec = $qry->fetch(PDO::FETCH_OBJ))) {
					$error = true;
					$error_message .= "Unknown user.\n";
				}
				else if (1 != $rec->enabled) {
					$error = true;
					$error_message .= "Access denied.\n";
				}
				else {
					$pwd_salt = $rec->pwd_salt;
					$confirmation_code = getNewIdString(25);
					$confirmation_url = SITE_URL."lostpassword.php?c=".$confirmation_code."&k=".substr(md5(LOSTPASSWORD_LINK_SALT.$confirmation_code.$pwd_salt.$email),7,10)."&e=".urlencode($email);
					setUserCompValue($rec->comp, "conf_code", $confirmation_code);
					setUserCompValue($rec->comp, "conf_exp", time()+60*60); // Now + 1 hour (60s * 60m)
					setUserCompValue($rec->comp, "conf_url", $confirmation_url);
					$qry = $db->prepare("update users set comp=:comp where id=:id");
					$qry->execute(array(":id" => $rec->id, ":comp" => $rec->comp));
					if (_DEBUG)
					{
						mail($email, "Please confirm your email", "Hi\n\nPlease click on this link to confirm your email and change your password :\n".$confirmation_url."\n\nBest regards\n\nThe team");
					}
					else {
						// TODO : replace this by an email check link
						die("Sending an changing password email is not available here.");
					}
					$LostPasswordStatus = CLostPasswordWait;
				}
			}
		}
	}
	else if (isset($_POST["frm"]) && ("2" == $_POST["frm"])) { // change password
		$LostPasswordStatus = CLostPasswordChange;
		$DefaultField = "Password";
		$email = isset($_POST["user"])?trim(strip_tags($_POST["user"])):"";
		if (empty($email)) {
			$error = true;
			$error_message .= "Fill your user email address to register.\n";
			$LostPasswordStatus = CLostPasswordForm;
		}
		else {
			$key = isset($_POST["k"])?trim(strip_tags($_POST["k"])):"";
			if (empty($key) || ($key != substr(md5($_SESSION["tempsalt"].LOSTPASSWORD_FORM_SALT.$email),7,10))) {
				$error = true;
				$error_message .= "Fill your user email address to register.\n";
				$LostPasswordStatus = CLostPasswordForm;
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
							$LostPasswordStatus = CLostPasswordForm;
						}
						else {
							$pwd_salt = getNewIdString(mt_rand(5,25));
							$qry = $db->prepare("update users set password=:pwd, pwd_salt=:s where email=:e");
							$qry->execute(array(":e" => $email, ":pwd" => getEncryptedPassword($password, $pwd_salt), ":s" =>$pwd_salt));
							$LostPasswordStatus = CLostPasswordOk;
						}
					}
				}
			}
			unset($_SESSION["tempsalt"]);
		}
	}
	else { // confirmation link received
		// sample confirmation URL : 
		// http://localhost/PHPUserForm/src/lostpassword.php?c=Aetjd4tHuoteB1azD4vtFETk2&k=8365ef7286&e=pprem%40pprem.net
		
		$confirmation_code = isset($_GET["c"])?trim($_GET["c"]):false;
		if ((false !== $confirmation_code) && (! empty($confirmation_code))) {
			$key = isset($_GET["k"])?trim($_GET["k"]):false;
			if ((false !== $key) && (! empty($key))) {
				$email = isset($_GET["e"])?trim($_GET["e"]):false;
				if ((false !== $email) && (! empty($email))) {
					$db = getPDOConnection();
					if (is_object($db)) {
						$qry = $db->prepare("select id, pwd_salt, comp from users where email=:email and enabled=1 limit 0,1");
						$qry->execute(array(":email" => $email));
						if (false !== ($rec = $qry->fetch(PDO::FETCH_OBJ))) {
							$confirmation_code = getUserCompValue($rec->comp, "conf_code");
							if ($key == substr(md5(LOSTPASSWORD_LINK_SALT.$confirmation_code.$rec->pwd_salt.$email),7,10)) {
								$confirmation_expiration = getUserCompValue($rec->comp, "conf_exp");
								if ($confirmation_expiration <= time()) {
									// confirmation code expired
									$LostPasswordStatus = CLostPasswordForm;
								}
								else {
									unsetUserCompKey($rec->comp, "conf_code");
									unsetUserCompKey($rec->comp, "conf_exp");
									unsetUserCompKey($rec->comp, "conf_url");
									$qry = $db->prepare("update users set comp=:comp where id=:id");
									$qry->execute(array(":comp" => $rec->comp, ":id" => $rec->id));
									$LostPasswordStatus = CLostPasswordChange;
									$DefaultField = "Password";
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
		<title>Lost Password - PHP User Password Basics</title>
		<style>
			.error {
				color: red;
				background-color: yellow;
			}
		</style>
	</head>
	<body><?php include_once(__DIR__."/inc/header.inc.php"); ?>
		<h2>Lost password</h2><?php
	if ($error && (! empty($error_message))) {
		print("<p class=\"error\">".nl2br($error_message)."</p>");
	}

	switch ($LostPasswordStatus) {
		case CLostPasswordForm:
?><form method="POST" action="lostpassword.php" onSubmit="return ValidForm();"><input type="hidden" name="frm" value="1">
	<p>
		<label for="User">User email</label><br>
		<input id="User" name="user" type="email" value="<?php print(isset($email)?htmlspecialchars($email):""); ?>" prompt="Your email address">
	</p>
	<p>
		<button type="submit">Get a password</button>
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
		return true;
	}
</script>
<p><a href="login.php">Log in</a></p>
<p><a href="signup.php">Sign up</a></p><?php
			break;
		case CLostPasswordWait:
?><p>We sent an confirmation email to your address. Please click on it.</p>
<p>Of course check your spams if you didn't see it in your inbox.</p><?php
			break;
		case CLostPasswordChange:
?><form method="POST" action="lostpassword.php" onSubmit="return ValidForm();"><input type="hidden" name="frm" value="2"><input type="hidden" name="user" value="<?php print(htmlspecialchars($email)); ?>" READONLY="readonly"><input type="hidden" name="k" value="<?php $_SESSION["tempsalt"] = getNewIdString(mt_rand(5,25)); print(substr(md5($_SESSION["tempsalt"].LOSTPASSWORD_FORM_SALT.$email),7,10)); ?>">
	<p>
		<label for="Password">Password</label><br>
		<input id="Password" name="password" type="password" value="" prompt="Your password">
	</p>
	<p>
		<label for="Password2">Password (rewrite the same)</label><br>
		<input id="Password2" name="password2" type="password" value="" prompt="Your password">
	</p>
	<p>
		<button type="submit">Set my password</button>
	</p>
</form>
<script>
	document.getElementById('<?php print($DefaultField); ?>').focus();
	function ValidForm() {
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
<p><a href="login.php">Log in</a></p>
<p><a href="signup.php">Sign up</a></p><?php
			break;
		case CLostPasswordOk:
?><p>Password changed. Please <a href="login.php">log in</a> to use the service.</p><?php
			break;
		default :
	}
	include_once(__DIR__."/inc/footer.inc.php"); ?></body>
</html>