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
	
	// sample activation URL : 
	// http://localhost/PHPUserForm/src/signup-wait.php?a=7xXC5qMHNKmQ8xIpdkvf8Bgjb&k=04f5fe7197&e=pprem%40pprem.net
	
	$activation_code = isset($_GET["a"])?trim($_GET["a"]):false;
	if ((false !== $activation_code) && (! empty($activation_code))) {
		$key = isset($_GET["k"])?trim($_GET["k"]):false;
		if ((false !== $key) && (! empty($key))) {
			$email = isset($_GET["e"])?trim($_GET["e"]):false;
			if ((false !== $email) && (! empty($email))) {
				// var_dump($_GET);
				// var_dump($activation_code);
				// var_dump($key);
				// var_dump($email);
				// exit;
				$db = getPDOConnection();
				if (is_object($db)) {
					// print("db ok");exit;
					$qry = $db->prepare("select id, pwd_salt, comp from users where email=:email and enabled=0 limit 0,1");
					$qry->execute(array(":email" => $email));
					if (false !== ($rec = $qry->fetch(PDO::FETCH_OBJ))) {
						// print("user trouvé");exit;
						// var_dump($rec); exit;
						$activation_code = getUserCompValue($rec->comp, "act_code");
						// var_dump($activation_code);
						// print($rec->pwd_salt); exit;
						// print(md5($activation_code.$rec->pwd_salt.$email)); exit;
						if ($key == substr(md5($activation_code.$rec->pwd_salt.$email),7,10)) {
							// print("clé ok");exit;
							$activation_expiration = getUserCompValue($rec->comp, "act_exp");
							if ($activation_expiration <= time()) {
								// activation code expired
								header("location: signup.php");
								exit;
							}
							else {
								unsetUserCompKey($comp, "act_code");
								unsetUserCompKey($comp, "act_exp");
								unsetUserCompKey($comp, "act_url");
								$qry = $db->prepare("update users set enabled=1, email_checked=1, email_check_ip=:ip, email_check_datetime=:dt, comp=:comp where id=:id");
								$qry->execute(array(":ip" => $_SERVER["REMOTE_ADDR"], ":dt" => date("YmdHis"), ":comp" => $comp, ":id" => $rec->id));
								header("location: signup-ok.php");
								exit;
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
	</head>
	<body><?php include_once(__DIR__."/inc/header.inc.php"); ?>
		<h2>Sign up</h2>
		<p>We sent an activation email to your address. Please click on it.</p>
		<p>Of course check your spams if you didn't see it in your inbox.</p>
<?php include_once(__DIR__."/inc/footer.inc.php"); ?></body>
</html>