<?php

header('Content-Type: application/json');

if (isset($_GET['query'])) {
	$query = $_GET['query'];

	$resultado = array();

	// Conexión a la base de datos
	$mysqli = new mysqli("localhost", "root", "", "xaca");
	if ($mysqli->connect_errno) {
		mostrarError('Hubo un error al conectarse a la base de datos.');
	}

	$mysqli->set_charset("utf8");

	switch ($query) {

		// QUERY: buscar / Parámetros obligatorios: texto / Parámetros opcionales: -
		case 'isValid':
			if (isset($_GET['username'])) {
				$username = $_GET['username'];
			} else {
				mostrarError('Falta un parámetro obligatorio.');
			}

			$data = isValidUsername($mysqli, $username);
			mostrarResultado($data);
			break;


		default:
			mostrarError('Falta un parámetro obligatorio.');

	}
} else {
	mostrarError('Falta un parámetro obligatorio.');
}

function mostrarError($msg = '') {
	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

function mostrarResultado($data = array()) {
	echo json_encode(array(
		'success' => true,
		'data' => $data
	));
}

function isValidUsername($mysqli, $username) {
	
	$sql = "SELECT usuario_id FROM usuario WHERE nombre_de_usuario = '$username'";
	if ($result = $mysqli->query($sql)) {
		return array(
			'isValid' => $result->num_rows == 0,
			'user' => $username
		);
	} else {
		mostrarError('Falló la consulta a la base de datos.');
	}

}