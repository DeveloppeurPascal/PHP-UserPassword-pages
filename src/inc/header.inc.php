<?php
	// PHP User Password Basics
	// (c) Patrick Prémartin
	//
	// Distributed under license AGPL.
	//
	// Infos and updates :
	// https://github.com/DeveloppeurPascal/PHP-UserPassword-Basics
?><header>
	<h1>PHP User Password Basics</h1>
	<p>Open source project available on <a href="https://github.com/DeveloppeurPascal/PHP-UserPassword-Basics">GitHub</a>.</p>
	<p><?php
		print("<button onclick=\"document.location = 'index.php'; return true;\">Home</button> ");
		if (hasCurrentUser()) {
			print("<button onclick=\"document.location = 'chgpassword.php'; return true;\">Change password</button> ");
			print("<button onclick=\"document.location = 'logout.php'; return true;\">Log out</button> ");
		}
		else {
			print("<button onclick=\"document.location = 'login.php'; return true;\">Log in</button> ");
			print("<button onclick=\"document.location = 'signup.php'; return true;\">Sign up</button> ");
		}
?></p>
</header>