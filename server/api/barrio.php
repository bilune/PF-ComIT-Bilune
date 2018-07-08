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
		case 'buscar':
			if (isset($_GET['term'])) {
				$texto = $_GET['term'];
			} else {
				mostrarError('Falta un parámetro obligatorio.');
			}

			$data = buscarBarriosPorTexto($mysqli, $texto);
			mostrarResultado($data);
			break;

		// QUERY: limites / Parámetros obligatorios: id / Parámetros opcionales: -
		case 'limites':
			if (isset($_GET['id'])) {
				$id = $_GET['id'];
			} else {
				mostrarError('Falta un parámetro obligatorio.');
			}

			$data = obtenerLimitesPorID($mysqli, $id);
			mostrarResultado($data);
			break;

		// QUERY: cercanos / Parámetros obligatorios: lat, lng / Parámetros opcionales: -
		case 'cercanos':
			if (isset($_GET['lat']) && isset($_GET['lng'])) {
				$lat = $_GET['lat'];
				$lng = $_GET['lng'];
			} else {
				mostrarError('Falta un parámetro obligatorio.');
			}

			$data = buscarBarrioPorCercania($mysqli, $lat, $lng);
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

function buscarBarriosPorTexto($mysqli, $texto) {
	
	$data = array();
	$sql = "SELECT
				barrio_id,
				nombre
			FROM
				barrio
			WHERE
				nombre LIKE '%$texto%' OR '% $texto%'
			LIMIT 5
			";
	
	if ($resultado = $mysqli->query($sql)) {
		while ($fila = $resultado->fetch_assoc()) {

			array_push($data, array(
				'id' => $fila['barrio_id'],
				'value' => $fila['nombre']
			));
		}
	} else {
		mostrarError('Hubo un error al efectuar la consulta.');
	}
	return $data;
}

function obtenerLimitesPorID($mysqli, $id) {
	$data = array();
	$sql = "SELECT nombre,
				ST_AsGeoJSON(limites) AS 'bounds',
				ST_AsGeoJSON(cuadro_delimitador) AS 'boundingBox'
			FROM
				barrio
			WHERE
				barrio_id = $id
			";
	
	if ($resultado = $mysqli->query($sql)) {
		$fila = $resultado->fetch_assoc();

		$data = array(
			'name' => $fila['nombre'],
			'id' => $id,
			'bounds' => json_decode($fila['bounds'])->coordinates[0],
			'boundingBox' => json_decode($fila['boundingBox'])->coordinates[0]
		);
	} else {
		mostrarError('Hubo un error al efectuar la consulta.');
	}
	return $data;
}

function buscarBarrioPorCercania($mysqli, $lat, $lng) {

	$data = array();
	$sql = "SELECT
				barrio_id,
				nombre,
				ST_CONTAINS(
					limites,
					POINT($lng, $lat)
				) AS contiene
			FROM
				barrio
			ORDER BY
				ST_DISTANCE(
					POINT($lng, $lat),
					limites
				) ASC
			LIMIT 5
			";

	if ($resultado = $mysqli->query($sql)) {
		while ($fila = $resultado->fetch_assoc()) {

			array_push($data, array(
				'id' => $fila['barrio_id'],
				'nombre' => $fila['nombre'],
				"contiene" => boolval($fila['contiene'])
			));
		}
	} else {
		mostrarError('Hubo un error al efectuar la consulta.');
	}
	return $data;
}

?>