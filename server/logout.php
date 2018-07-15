<?php

session_start();
$_SESSION['loggedin'] = false;
unset($_SESSION['username']);
session_destroy();

if (isset($_GET['return'])) {
	header('Location: ' . base64_decode($_GET['return']));
} else {
	header('Location: http://localhost/xaca/dashboard.php');
}

?>