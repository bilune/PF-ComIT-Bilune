<?php

$host_db = 'localhost';
$user_db = 'root';
$pass_db = '';
$db_name = 'xaca';
$tbl_name = 'usuario';

$username = $_POST['usuario'];
$form_pass = $_POST['password'];
$email = $_POST['email'];
$nombre = $_POST['nombre'];

$hash = password_hash($form_pass, PASSWORD_BCRYPT);

$conexion = new mysqli($host_db, $user_db, $pass_db, $db_name);

if ($conexion->connect_error) {
	die('La conexión falló: ' . $conexion->connect_error);
}
$conexion->set_charset('utf8');

$buscar_usuario = "SELECT * FROM $tbl_name WHERE nombre_de_usuario = '$username'";

$result = $conexion->query($buscar_usuario);

if ($result->num_rows != 0) {
	echo '<br> El nombre de usuario ya ha sido tomado. <br>';
	echo '<a href=index.html>Por favor escoja otro nombre</a>';
} else {
	$query = "	INSERT INTO
					usuario (nombre_de_usuario, contraseña, email, nombre)
				VALUES
					('$username', '$hash', '$email', '$nombre')";

	if ($conexion->query($query)) {
		header('Location: http://localhost/xaca/dashboard.php');
	} else {
		echo 'Error al crear el usuario. <br>' . $conexion->error;
	}
}

$conexion->close();