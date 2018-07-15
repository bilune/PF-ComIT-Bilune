<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "xaca");
if ($mysqli->connect_errno) {
	mostrarError('Hubo un error al conectarse a la base de datos.');
}

$mysqli->set_charset('utf8');


if (count($_GET) > 0) {
	if (isset($_GET['query'])) {
		$query = $_GET['query'];
	} else {
		mostrarError('Falta un parámetro obligatorio.');
	}
	
	$resultado = array();

	switch ($query) {

		// QUERY: markers / Parámetros obligatorios: - / Parámetros opcionales: -
		case 'markers':
			$data = obtenerMarcadores($mysqli);
			mostrarResultado($data);
			break;

		// QUERY: historias / Parámetros obligatorios: barrio || usuario / Parámetros opcionales: antes_de
		case 'historias':

			$antes_de = isset($_GET['antes_de']) ? $_GET['antes_de'] : '';

			if (isset($_GET['barrio'])) {

				$barrio = $_GET['barrio'];
				$data = obtenerHistoriasPorBarrio($mysqli, $barrio, $antes_de);

			} else if (isset($_GET['usuario'])) {

				$usuario = $_GET['usuario'];
				$data = obtenerHistoriasPorUsuario($mysqli, $usuario, $antes_de);

			} else {
				mostrarError('Falta un parámetro obligatorio.');
			}

			mostrarResultado($data);
			break;

		// QUERY: historia - Parámetros obligatorios: id - Parámetros opcionales: -
		case 'historia':
			if (isset($_GET['id'])) {
				$id = $_GET['id'];
			} else {
				mostrarError('Falta un parámetro obligatorio.');
			}

			$data = obtenerHistoriaPorID($mysqli, $id);
			mostrarResultado($data);
			break;

		// QUERY: previsualizar - Parámetros obligatorios: url - Parámetros opcionales: -
		case 'previsualizar':
			if (isset($_GET['url'])) {
				$url = $_GET['url'];
			} else {
				mostrarError('Falta un parámetro obligatorio.');
			}

			$data = previsualizarHistoriaDesdeURL($mysqli, $url);
			mostrarResultado($data);
			break;

		default:
			mostrarError('Falta un parámetro obligatorio.');

	}

} else if (count($_POST) > 0) {
	checkSession();

	switch ($_POST['categoria']) {
		case 'noticias':
			$data = agregarNoticia($mysqli);
			mostrarResultado($data);
			break;
		case 'eventos':
			$data = agregarEvento($mysqli);
			mostrarResultado($data);
			break;
		case 'reportes':
			$data = agregarReporte($mysqli);
			mostrarResultado($data);
			break;
		default:
			mostrarError('Falta un parámetro obligatorio.');
	}
	
} else {
	mostrarError('Falta un parámetro obligatorio.');
}

function mostrarError($msg = '', $err_code = 400) {
	http_response_code($err_code);
	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

function mostrarResultado($data = array()) {
	http_response_code(200);
	echo json_encode(array(
		'success' => true,
		'data' => $data
	));
}

function checkSession() {
	session_start();
	if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
		mostrarError('No autorizado.', 401);
	}
	
}

function obtenerMarcadores($mysqli) {

	$data = array();
	$sql = 'SELECT `historia_id`, `tipo_historia` , X(`ubicacion`) AS lng, Y(`ubicacion`) AS lat FROM `historia` ORDER BY `fecha_creacion` DESC LIMIT 0, 100';
	if ($resultado = $mysqli->query($sql)) {
		while ($fila = $resultado->fetch_assoc()) {
			array_push($data, array(
				'id' => $fila['historia_id'],
				'category' => $fila['tipo_historia'],
				'geometry' => array(
					'lat' => floatval($fila['lat']),
					'lng' => floatval($fila['lng'])
				)
			));
		}
	} else {
		mostrarError('Hubo un error al efectuar la consulta.');
	}
	return $data;
}

function obtenerHistoriasPorBarrio($mysqli, $barrio, $antes_de) {

	$data = array();
	$sql = "SELECT
				historia.historia_id,
				usuario.nombre AS autor,
				historia.titulo,
				historia.imagen,
				X(historia.ubicacion) AS lng,
				Y(historia.ubicacion) AS lat,
				historia.fecha_creacion,
				historia.tipo_historia,
				historia.url_noticia,
				historia.solucionado,
				historia.tipo_reporte,
				historia.descripcion,
				historia.dia_hora_evento,
				historia.direccion_evento
			FROM
				historia
			INNER JOIN historia_barrio ON historia.historia_id = historia_barrio.historia_id
			INNER JOIN usuario ON historia.usuario_id = usuario.usuario_id
			WHERE
				historia_barrio.barrio_id = $barrio
			";
	
	if (!empty($antes_de)) {

		$antes_de_datetime = new DateTime(null, new DateTimeZone('America/Argentina/Buenos_Aires'));
		$antes_de_datetime->setTimeStamp($antes_de);
		$antes_de = $antes_de_datetime->format('Y-m-d H:i:s');

		$sql .= " AND historia.fecha_creacion < '$antes_de'";

	}
	$sql .= " ORDER BY historia.fecha_creacion DESC LIMIT 0, 10";

	if ($resultado = $mysqli->query($sql)) {
		while ($fila = $resultado->fetch_assoc()) {

			$fecha_creacion = new DateTime($fila['fecha_creacion'], new DateTimeZone('America/Argentina/Buenos_Aires'));

			array_push($data, array(
				'id' => $fila['historia_id'],
				'html' => toHTML($fila),
				'fecha_creacion' => $fecha_creacion->getTimestamp(),
				'geometry' => array(
					'lat' => floatval($fila['lat']),
					'lng' => floatval($fila['lng'])
				),
				'category' => $fila['tipo_historia']
			));
		}
	} else {
		mostrarError('Hubo un error al efectuar la consulta.');
	}
	return $data;

}

function obtenerHistoriasPorUsuario($mysqli, $usuario, $antes_de) {

	$data = array();
	$sql = "SELECT
				historia.historia_id,
				usuario.nombre AS autor,
				historia.titulo,
				historia.imagen,
				X(historia.ubicacion) AS lng,
				Y(historia.ubicacion) AS lat,
				historia.fecha_creacion,
				historia.tipo_historia,
				historia.url_noticia,
				historia.solucionado,
				historia.tipo_reporte,
				historia.descripcion,
				historia.dia_hora_evento,
				historia.direccion_evento,
				GROUP_CONCAT(barrio.nombre) AS barrios
			FROM
				historia
			INNER JOIN usuario ON historia.usuario_id = usuario.usuario_id
			INNER JOIN historia_barrio ON historia.historia_id = historia_barrio.historia_id
			INNER JOIN barrio ON historia_barrio.barrio_id = barrio.barrio_id
			WHERE
				usuario.nombre_de_usuario = '$usuario'
			";
	
	if (!empty($antes_de)) {

		$antes_de_datetime = new DateTime(null, new DateTimeZone('America/Argentina/Buenos_Aires'));
		$antes_de_datetime->setTimeStamp($antes_de);
		$antes_de = $antes_de_datetime->format('Y-m-d H:i:s');

		$sql .= " AND historia.fecha_creacion < '$antes_de'";

	}
	$sql .= " GROUP BY
				historia.historia_id
			ORDER BY 
				historia.fecha_creacion DESC
			LIMIT 0, 10";

	if ($resultado = $mysqli->query($sql)) {
		while ($fila = $resultado->fetch_assoc()) {

			$fecha_creacion = new DateTime($fila['fecha_creacion'], new DateTimeZone('America/Argentina/Buenos_Aires'));

			array_push($data, array(
				'id' => $fila['historia_id'],
				'html' => toHTML($fila),
				'fecha_creacion' => $fecha_creacion->getTimestamp(),
				'geometry' => array(
					'lat' => floatval($fila['lat']),
					'lng' => floatval($fila['lng'])
				),
				'category' => $fila['tipo_historia']
			));
		}
	} else {
		mostrarError('Hubo un error al efectuar la consulta.');
	}
	return $data;

}

function obtenerHistoriaPorID($mysqli, $id) {
	$data = array();
	$sql = "SELECT
				historia.historia_id,
				usuario.nombre AS autor,
				historia.titulo,
				historia.imagen,
				historia.fecha_creacion,
				historia.tipo_historia,
				historia.url_noticia,
				historia.solucionado,
				historia.tipo_reporte,
				historia.descripcion,
				historia.dia_hora_evento,
				historia.direccion_evento
			FROM
				historia
			INNER JOIN usuario ON historia.usuario_id = usuario.usuario_id
			WHERE
				historia.historia_id = $id
			";

	$mysqli->set_charset('utf8');
	if ($resultado = $mysqli->query($sql)) {
		$fila = $resultado->fetch_assoc();
		$fecha_creacion = new DateTime($fila['fecha_creacion'], new DateTimeZone('America/Argentina/Buenos_Aires'));

		$data = array(
			'id' => $fila['historia_id'],
			'html' => toHTML($fila),
			'fecha_creacion' => $fecha_creacion->getTimestamp()
		);

	} else {
		mostrarError('Hubo un error al efectuar la consulta.');
	}
	return $data;

}

function obtenerDataDesdeURL($url) {
	require_once('../OpenGraph.php');
	return OpenGraph::fetch($url);
}

function previsualizarHistoriaDesdeURL($mysqli, $url) {

	$data = array();
	$url = filter_var($url, FILTER_VALIDATE_URL, array('flags' => FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_PATH_REQUIRED));
	if ($url && $graph = obtenerDataDesdeURL($url)) {

		$noticia_data = array(
			'tipo_historia' => 'noticias',
			'url_noticia' => $url,
			'titulo' => $graph->title ? html_entity_decode($graph->title) : '',
			'imagen' => $graph->image ? html_entity_decode($graph->image) : ''
		);

		$data = array(
			'html' => toHTML($noticia_data)
		);

		session_start();
		if (!empty($_SESSION['loggedin'])) {
			$_SESSION['ultima_noticia'] = $noticia_data;
		}

	} else {
		mostrarError('La URL ingresada no es válida.');
	}


	return $data;
}

function toHTML($fila) {

	$historia = '';
	$tipo_historia = $fila['tipo_historia'];

	switch ($tipo_historia) {

	case 'noticias':
		$url = ucfirst(parse_url($fila['url_noticia'], PHP_URL_HOST));

		$historia = 
		'<a href="'.$fila['url_noticia'].'" target="_blank" class="card card__noticia"'. ( isset($fila['historia_id']) ? 'id="id'. $fila['historia_id'] .'"' : '' ) .'>
			<div class="card-body">
				' .
				(!empty($fila['imagen']) ?
				'<figure class="card__img ml-4 mb-2">
					<img src="'.$fila['imagen'].'" alt="'.$fila['titulo'].'">
				</figure>' : '')
				. '
				<h4 class="card-title m-2">
					'.$fila['titulo'].'
					<span class="badge ml-2 px-2">'.ucfirst($tipo_historia).'</span>
				</h4>
				<span class="card-subtitle m-2 text-muted">'.$url.'</span>
			</div>'.
			( isset($fila['autor']) ? '<div class="card-body pt-0 mx-2 text-muted">
				<small>Publicado <span class="card__timeago"></span> por '. $fila['autor'] .'</small>
			</div>' : '' ) .
		'</a>';
		break;

	case 'eventos':
		setlocale(LC_ALL,"es-AR");

		$datetime = new DateTime($fila['dia_hora_evento'], new DateTimeZone('America/Argentina/Buenos_Aires'));

		$historia = 
		'<article class="card card__evento p-2" id="id'. $fila['historia_id'] .'">
			<div class="card-body">
				' .
				(!empty($fila['imagen']) ?
				'<figure class="card__img ml-4 mb-2">
					<img src="images/historias/'. $fila['imagen'] .'" alt="'. $fila['titulo'] .'">
				</figure>' : '')
				. '
				<h4 class="card-title m-2">
					'. $fila['titulo'] .'
					<span class="badge ml-2 px-2">'.ucfirst($tipo_historia).'</span>
				</h4>
				<div class="card-subtitle mx-2 my-3 text-muted">
					<span class="my-2 mr-3">
						<img src="icons/calendar-clock.svg" class="mr-1" alt="Icono fecha">
						'. strftime('%d de %B de %Y', $datetime->getTimestamp()) .' - 
						'. strftime('%H:%M', $datetime->getTimestamp()) .' hs
					</span>
				</div>
				<p class="card-text m-2">
					'. $fila['descripcion'] .'
				</p>
				
			</div>
			<div class="card-body pt-0 mx-2 text-muted">
					<small>Publicado <span class="card__timeago"></span> por '. $fila['autor'] .'</small>
				</div>
		</article>';
		break;

	case 'reportes':
		$historia =
		'<article class="card card__reporte p-2" id="id'. $fila['historia_id'] .'">
			<div class="card-body">
				' .
				(!empty($fila['imagen']) ?
				'<figure class="card__img ml-4 mb-2">
					<img src="images/historias/'. $fila['imagen'] .'" alt="'. $fila['titulo'] .'">
				</figure>' : '')
				. '

				<h4 class="card-title m-2">
						'.$fila['titulo'].'
						<span class="badge ml-2 px-2">'. ucfirst($tipo_historia) .'</span>
					</h4>
					<div class="card-subtitle mx-2 my-3 text-muted">
						<span class="mr-2">
							<img src="icons/'. ($fila['solucionado'] ? 'check.svg' : 'alert.svg') .'" class="mr-1">
							<strong>'. ($fila['solucionado'] ? 'SOLUCIONADO' : 'SIN SOLUCIONAR') .'</strong>
						</span>
						<span class="m-2">
							'. $fila['tipo_reporte'] .'
						</span>
					</div>
					<p class="card-text m-2">
						'. $fila['descripcion'] .'
					</p>
			</div>
			<div class="card-body pt-0 mx-2 text-muted">
					<small>Publicado <span class="card__timeago"></span> por '. $fila['autor'] .'</small>
				</div>
		</article>';
		break;
	}

	return str_replace(array("\n", "\t"), '', $historia);

}

function agregarNoticia($mysqli) {

	// Se obtiene la información de la noticia previamente cargada por el usuario
	if (!empty($_SESSION['ultima_noticia']) && $_SESSION['ultima_noticia']['url_noticia'] === $_POST['url']) {
		$data = $_SESSION['ultima_noticia'];

	// En el caso de que no haya sido cargada previamente, se carga
	} else {
		$graph = obtenerDataDesdeURL($_POST['url']);
		if ($graph) {
			$data = array(
				'tipo_historia' => 'noticias',
				'url_noticia' => $_POST['url'],
				'titulo' => $graph->title ? html_entity_decode($graph->title) : '',
				'imagen' => $graph->image ? html_entity_decode($graph->image) : ''
			);
		} else {
			mostrarError('No se pudo obtener la información de la URL.');
		}
	}

	$ubicacion = json_decode($_POST['ubicacion']);

	$stmt = $mysqli->prepare("INSERT INTO historia (titulo, imagen, ubicacion, usuario_id, tipo_historia, url_noticia) VALUES (?, ?, POINT(?, ?), ?, ?, ?)");
	$stmt->bind_param(
		"ssddiss",
		$titulo,
		$imagen,
		$ubicacion_lng,
		$ubicacion_lat,
		$usuario_id,
		$tipo_historia,
		$url_noticia
	);

	$titulo = $data['titulo'];
	$imagen = $data['imagen'];
	$ubicacion_lng = floatval($ubicacion->lng);
	$ubicacion_lat = floatval($ubicacion->lat);
	$usuario_id = intval($_SESSION['usuario_id']);
	$tipo_historia = $_POST['categoria'];
	$url_noticia = $_POST['url'];

	if (!$stmt->execute()) {
		mostrarError('No se pudo insertar el reporte en la base de datos. ' . $stmt->error);
	}

	$historia_id = $mysqli->insert_id;
	$barrios = json_decode($_POST['barrios']);
	agregarHistoriaBarrio($mysqli, $barrios, $historia_id);

	$now = new DateTime(null, new DateTimeZone('America/Argentina/Buenos_Aires'));

	return array(
		'id' => $historia_id,
		'geometry' => array(
			'lat' => $ubicacion_lat,
			'lng' => $ubicacion_lng
		),
		'html' => toHTML( array(
			'titulo' => $titulo,
			'imagen' => $imagen,
			'autor' => $_SESSION['nombre'],
			'tipo_historia' => $tipo_historia,
			'url_noticia' => $url_noticia,
			'historia_id' => $historia_id
		)),
		'barrios' => $barrios,
		'categoria' => $tipo_historia,
		'fecha_creacion' => $now->getTimestamp()
	);
	
}

function agregarEvento($mysqli) {

	$ubicacion = json_decode($_POST['ubicacion']);

	$stmt = $mysqli->prepare("INSERT INTO historia (titulo, imagen, ubicacion, usuario_id, tipo_historia, descripcion, dia_hora_evento) VALUES (?, ?, POINT(?, ?), ?, ?, ?, ?)");
	$stmt->bind_param(
		"ssddisss",
		$titulo,
		$imagen,
		$ubicacion_lng,
		$ubicacion_lat,
		$usuario_id,
		$tipo_historia,
		$descripcion,
		$dia_hora_evento
	);

	$titulo = $_POST['titulo']; // required
	$imagen = uploadImage(); // optional
	$ubicacion_lng = floatval($ubicacion->lng);
	$ubicacion_lat = floatval($ubicacion->lat);
	$usuario_id = intval($_SESSION['usuario_id']);
	$tipo_historia = $_POST['categoria']; // optional
	$descripcion = !empty($_POST['descripcion']) ? $_POST['descripcion'] : ''; // optional
	$dia_hora_evento = $_POST['fecha'] . ' ' . $_POST['hora']; // required

	if (!$stmt->execute()) {
		mostrarError('No se pudo insertar el reporte en la base de datos. ' . $stmt->error);
	}

	$barrios = json_decode($_POST['barrios']);
	$historia_id = $mysqli->insert_id;
	agregarHistoriaBarrio($mysqli, $barrios, $historia_id);

	$now = new DateTime(null, new DateTimeZone('America/Argentina/Buenos_Aires'));

	return array(
		'id' => $historia_id,
		'geometry' => array(
			'lat' => $ubicacion_lat,
			'lng' => $ubicacion_lng
		),
		'html' => toHTML( array(
			'titulo' => $titulo,
			'imagen' => $imagen,
			'autor' => $_SESSION['nombre'],
			'tipo_historia' => $tipo_historia,
			'descripcion' => $descripcion,
			'dia_hora_evento' => $dia_hora_evento,
			'historia_id' => $historia_id
		)),
		'barrios' => $barrios,
		'categoria' => $tipo_historia,
		'fecha_creacion' => $now->getTimestamp()
	);

}

function agregarReporte($mysqli) {

	$ubicacion = json_decode($_POST['ubicacion']);

	$stmt = $mysqli->prepare("INSERT INTO historia (titulo, imagen, ubicacion, usuario_id, tipo_historia, solucionado, tipo_reporte, descripcion) VALUES (?, ?, POINT(?, ?), ?, ?, ?, ?, ?)");
	$stmt->bind_param(
		"ssddisiss",
		$titulo,
		$imagen,
		$ubicacion_lng,
		$ubicacion_lat,
		$usuario_id,
		$tipo_historia,
		$solucionado,
		$tipo_reporte,
		$descripcion
	);

	// Inputs
	$titulo = $_POST['titulo']; // required
	$imagen = uploadImage(); // optional
	$ubicacion_lng = floatval($ubicacion->lng);
	$ubicacion_lat = floatval($ubicacion->lat);
	$usuario_id = intval($_SESSION['usuario_id']);
	$tipo_historia = $_POST['categoria'];
	$solucionado = 0;
	$tipo_reporte = $_POST['tipo-reporte']; // required
	$descripcion = !empty($_POST['descripcion']) ? $_POST['descripcion'] : ''; // optional

	if (!$stmt->execute()) {
		mostrarError('No se pudo insertar el reporte en la base de datos.');
	}

	$barrios = json_decode($_POST['barrios']);
	$historia_id = $mysqli->insert_id;
	agregarHistoriaBarrio($mysqli, $barrios, $historia_id);

	$now = new DateTime(null, new DateTimeZone('America/Argentina/Buenos_Aires'));

	return array(
		'id' => $historia_id,
		'geometry' => array(
			'lat' => $ubicacion_lat,
			'lng' => $ubicacion_lng
		),
		'html' => toHTML( array(
			'titulo' => $titulo,
			'imagen' => $imagen,
			'autor' => $_SESSION['nombre'],
			'tipo_historia' => $tipo_historia,
			'descripcion' => $descripcion,
			'solucionado' => $solucionado,
			'tipo_reporte' => $tipo_reporte,
			'historia_id' => $historia_id
		)),
		'barrios' => $barrios,
		'categoria' => $tipo_historia,
		'fecha_creacion' => $now->getTimestamp()
	);

}

// Sube una imagen al servidor
function uploadImage() {

	if (empty($_FILES['imagen']) || ($_FILES['imagen']['size'] == 0 && $_FILES['imagen']['error'] == 0)) { 
		return '';
	}

	$dir_subida = '../../images/historias/';
	$ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);

	$file_name = uniqid('img_') .'.'. $ext;
	$fichero_subido = $dir_subida . $file_name;
	if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $fichero_subido)) {
		mostrarError('No pudo subirse la imagen.');
	}

	return $file_name;
}

function agregarHistoriaBarrio($mysqli, $barrios, $historia_id) {

	$stmt = $mysqli->prepare("INSERT INTO historia_barrio VALUES (?, ?)");

	foreach ($barrios as $barrio_id) {
		$barrio_id = intval($barrio_id);
		$historia_id = intval($historia_id);

		$stmt->bind_param("ii", $barrio_id, $historia_id);
		if (!$stmt->execute()) {
			mostrarError('No se pudo insertar el reporte en la base de datos. ' . $stmt->error);
		}
	}

	return true;
}


?>