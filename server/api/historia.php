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

	$mysqli->set_charset('utf8');

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

} else {
	mostrarError('Falta un parámetro obligatorio.');
}

function mostrarError($msg = '') {
	http_response_code(400);
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

		$antes_de_datetime = new DateTime();
		$antes_de_datetime->setTimeStamp($antes_de);
		$antes_de = $antes_de_datetime->format('Y-m-d H:i:s');

		$sql .= " AND historia.fecha_creacion < '$antes_de'";

	}
	$sql .= " ORDER BY historia.fecha_creacion DESC LIMIT 0, 1";

	if ($resultado = $mysqli->query($sql)) {
		while ($fila = $resultado->fetch_assoc()) {

			$fecha_creacion = new DateTime($fila['fecha_creacion']);

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

		$antes_de_datetime = new DateTime();
		$antes_de_datetime->setTimeStamp($antes_de);
		$antes_de = $antes_de_datetime->format('Y-m-d H:i:s');

		$sql .= " AND historia.fecha_creacion < '$antes_de'";

	}
	$sql .= " GROUP BY
				historia.historia_id
			ORDER BY 
				historia.fecha_creacion DESC
			LIMIT 0, 1";

	if ($resultado = $mysqli->query($sql)) {
		while ($fila = $resultado->fetch_assoc()) {

			$fecha_creacion = new DateTime($fila['fecha_creacion']);

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
		$fecha_creacion = new DateTime($fila['fecha_creacion']);

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

function previsualizarHistoriaDesdeURL($mysqli, $url) {

	$data = array();
	$url = filter_var($url, FILTER_VALIDATE_URL, array('flags' => FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_PATH_REQUIRED));
	if ($url) {
		require_once('../OpenGraph.php');
		$graph = OpenGraph::fetch($url);

		if ($graph) {

			$data = array(
				'html' => toHTML(array(
					'tipo_historia' => 'noticias',
					'url_noticia' => $graph->url ? utf8_decode(html_entity_decode($graph->url)) : '',
					'titulo' => $graph->title ? utf8_decode(html_entity_decode($graph->title)) : '',
					'imagen' => $graph->image ? utf8_decode(html_entity_decode($graph->image)) : ''
				))
			);

		} else {
			mostrarError('No se pudo detectar la información para previsualizar esta URL.');
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
			<figure class="card__img ml-4 mb-2">
					<img src="'.$fila['imagen'].'" alt="'.$fila['titulo'].'">
				</figure>
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

		$datetime = new DateTime($fila['dia_hora_evento']);

		$historia = 
		'<article class="card card__evento p-2" id="id'. $fila['historia_id'] .'">
			<div class="card-body">
				<figure class="card__img ml-4 mb-2">
					<img src="'. $fila['imagen'] .'" alt="'. $fila['titulo'] .'">
				</figure>
				<h4 class="card-title m-2">
					'. $fila['titulo'] .'
					<span class="badge ml-2 px-2">'.ucfirst($tipo_historia).'</span>
				</h4>
				<div class="card-subtitle mx-2 my-3 text-muted">
					<span class="my-2 mr-3">
						<img src="icons/calendar.svg" class="mr-1" alt="Icono fecha">
						'. strftime('%d de %B de %Y', $datetime->getTimestamp()) .'
					</span>
					<span class="my-2 mr-3">
						<img src="icons/time.svg" class="mr-1" alt="Icono hora">
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
				<figure class="card__img img-fluid">
					<img src="'. $fila['imagen'] .'" alt="'. $fila['titulo'] .'">
				</figure>
				<h4 class="card-title m-2">
					'.$fila['titulo'].'
					<span class="badge ml-2 px-2">'. ucfirst($tipo_historia) .'</span>
				</h4>
				<div class="card-subtitle mx-2 my-3 text-muted">
					<span class="m-2">
						<img src="icons/'. ($fila['solucionado'] ? 'tick.svg' : 'close.svg') .'" class="mr-1">
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


?>