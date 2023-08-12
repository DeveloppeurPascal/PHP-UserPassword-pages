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

	// This page is only available when a user is connected.
	if (! hasCurrentUser()) {
		header("location: index.php");
		exit;
	}

	define("CChgPasswordForm", 1);
	define("CChgPasswordOk", 2);
	
	$ChgPasswordStatus = CChgPasswordForm;

	$error = false;
	$error_message = "";
	$DefaultField = "OldPassword";

	if (isset($_POST["frm"]) && ("1" == $_POST["frm"])) {
		$oldpassword = isset($_POST["oldpassword"])?trim(strip_tags($_POST["oldpassword"])):"";
		if (empty($oldpassword)) {
			$error = true;
			$error_message .= "Fill your password to register.\n";
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
						$qry = $db->prepare("select password, pwd_salt, enabled from users where id=:id limit 0,1");
						$qry->execute(array(":id" => getCurrentUserId()));
						if (false === ($rec = $qry->fetch(PDO::FETCH_OBJ))) {
							$error = true;
							$error_message .= "Unknown user.\n";
						}
						else if (1 != $rec->enabled) {
							$error = true;
							$error_message .= "Access denied.\n";
						}
						else if (getEncryptedPassword($oldpassword, $rec->pwd_salt) != $rec->password) {
							$error = true;
							$error_message .= "Wrong current password.\n";
						}
						else {
							$pwd_salt = getNewIdString(mt_rand(5,25));
							$qry = $db->prepare("update users set password=:pwd, pwd_salt=:s where id=:id");
							$qry->execute(array(":id" => getCurrentUserId(), ":pwd" => getEncryptedPassword($password, $pwd_salt), ":s" =>$pwd_salt));
							$ChgPasswordStatus = CChgPasswordOk;
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
		<title>Change password - PHP User Password Basics</title>
		<style>
			.error {
				color: red;
				background-color: yellow;
			}
		</style>
	</head>
	<body><?php include_once(__DIR__."/inc/header.inc.php"); ?>
		<h2>Change password</h2><?php
	if ($error && (! empty($error_message))) {
		print("<p class=\"error\">".nl2br($error_message)."</p>");
	}

	switch ($ChgPasswordStatus) {
		case CChgPasswordForm:
?><form method="POST" action="chgpassword.php" onSubmit="return ValidForm();"><input type="hidden" name="frm" value="1">
	<p>
		<label for="OldPassword">Old password</label><br>
		<input id="OldPassword" name="oldpassword" type="password" value="" prompt="Your actual password">
	</p>
	<p>
		<label for="Password">New password</label><br>
		<input id="Password" name="password" type="password" value="" prompt="Your new password">
	</p>
	<p>
		<label for="Password2">New password (rewrite the same)</label><br>
		<input id="Password2" name="password2" type="password" value="" prompt="Your new password">
	</p>
	<p>
		<button type="submit">Change my password</button>
	</p>
</form><script>
	document.getElementById('<?php print($DefaultField); ?>').focus();
	function ValidForm() {
		opwd = document.getElementById('OldPassword');
		if (0 == opwd.value.length) {
			opwd.focus();
			window.alert('Current password needed !');
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
</script><?php
			break;
		case CChgPasswordOk:
?><p>Password changed.</p><?php
			break;
		default :
	}
	include_once(__DIR__."/inc/footer.inc.php"); ?></body>
</html>