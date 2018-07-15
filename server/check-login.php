<?php

session_start();

$host_db = 'localhost';
$user_db = 'root';
$pass_db = '';
$db_name = 'xaca';
$tbl_name = 'usuario';

$conexion = new mysqli($host_db, $user_db, $pass_db, $db_name);

if ($conexion->connect_error) {
	die('La conexión falló: ' . $conexion->connect_error);
}

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM $tbl_name WHERE nombre_usuario = '$username'";

$result = $conexion->query($sql);

if ($result->num_rows > 0) {
	$row = $result->fetch_assoc();
	if (password_verify($password, $row['password'])) {
		$_SESSION['loggedin'] = true;
		$_SESSION['username'] = $username;
		$_SESSION['start'] = time();
		$_SESSION['expire'] = $_SESSION['start'] + (5 * 60);
	
		header('Location: http://localhost/xaca/dashboard.php');
	} else {
		echo 'Username o Password incorrectos.';
		echo '<br><a href=login.html>Volver a intentarlo</a>';
	}
}

$conexion->close();