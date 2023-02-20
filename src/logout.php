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

	session_destroy();
	header("location: index.php");
