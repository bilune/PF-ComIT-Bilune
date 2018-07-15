<?php
session_start();

if (isset($_GET['username'])) {
	$username = $_GET['username'];
} else {
	error404();
}

$mysqli = new mysqli("localhost", "root", "", "xaca");
if ($mysqli->connect_errno) {
	error404();
}
$mysqli->set_charset("utf8");

// Datos de usuario
$sql = "SELECT
			nombre,
			nombre_de_usuario,
			biografia,
			imagen_perfil,
			imagen_portada
		FROM
			usuario
		WHERE
			nombre_de_usuario = '$username'";

if ($resultado = $mysqli->query($sql)) {
	$datos_usuario  = $resultado->fetch_assoc();
} else {
	error404();
}

// Historias del usuario
$sql2 = "SELECT
			historia.*
		FROM
			historia
		INNER JOIN usuario ON historia.usuario_id = usuario.usuario_id
		WHERE
			usuario.nombre_de_usuario = '$username'
		ORDER BY
			historia.fecha_creacion
		DESC";

?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Xacá</title>

	<link rel="stylesheet" href="css/bootstrap.css">
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
	<link rel="stylesheet" href="css/dashboard.css">
	<link rel="stylesheet" href="css/profile.css">
	<link href="https://fonts.googleapis.com/css?family=Poppins:700|Roboto:400,500" rel="stylesheet">

</head>

<body>
	<div class="app expanded">

		<!-- Navbar -->
		<nav class="navbar navbar-expand navbar-light fixed-top bg-light">

			<!-- Navbar Logo -->
			<a class="navbar-brand nav__logo">
				<img src="icons/xaca-logo-color.svg" height="30" width="80" alt="Logo xacá">
			</a>

			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Alternar navegación">
				<span class="navbar-toggler-icon"></span>
			</button>

		<?php
			if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
				$username = $_SESSION['username'];
				$imagen_perfil = !empty($datos_usuario['imagen_perfil']) ? 'images/usuarios/' . $datos_usuario['imagen_perfil'] : 'images/custom-profile.jpg';
				echo '
			<li class="nav-item dropdown ml-auto">
				<a class="dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<img class="rounded-circle" src="' . $imagen_perfil . '" width="40" height="40">
				</a>
				<div class="dropdown-menu" aria-labelledby="navbarDropdown">
					<a class="dropdown-item" href="dashboard.php">
						<img src="icons/dashboard.svg">
						Inicio
					</a>
					<a class="dropdown-item" href="setup.php">
						<img class="rounded-circle" src="icons/settings.svg">
						Configuración
					</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="server/logout.php?return='. base64_encode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) .'">
						<img class="rounded-circle" src="icons/logout.svg">
						Cerrar sesión
					</a>
				</div>
			</li>
				';
		} else {
				echo '
			<a href="login.php" class="btn btn-primary ml-auto">
				<span class="d-none d-sm-inline">Iniciar sesión</span>
				<span class="d-inline d-sm-none">Entrar</span>
			</a>';
			}
		?>			
		</nav>


		<div class="dashboard pb-5">
			
			<!-- Profile -->
		<?php

		if (!empty($datos_usuario['imagen_portada'])) {
			$imagen_portada = 
				'<figure class="profile__header">
					<img src="images/usuarios/' . $datos_usuario['imagen_portada'] . '" alt="Imagen de portada de ' . $datos_usuario['nombre'] . '">
				</figure>';
		} else {
			$imagen_portada = '';
		}

		$imagen_perfil = !empty($datos_usuario['imagen_perfil']) ? 'images/usuarios/' . $datos_usuario['imagen_perfil'] : 'images/custom-profile.jpg';

		echo 
			'<div class="profile bg-white px-3 pb-2 pb-lg-4 mb-4">
				' . $imagen_portada . '
				<img src="' . $imagen_perfil . '" class="profile__picture img-thumbnail" alt="Imagen de perfil de ' . $datos_usuario['nombre'] . '">

				<h1 class="profile__name h3 mb-0">' . $datos_usuario['nombre'] . '</h1>
				<div class="profile__username text-muted">@' . $datos_usuario['nombre_de_usuario'] . '</div>
				<div class="profile__bio text-center mt-3">
					' . $datos_usuario['biografia'] . '
				</div>
			</div>';

		?>

			<div class="dashboard__stories px-3"></div>
			<img class="loader d-none my-3 mx-auto" src="icons/loader.gif" style="height:30px;width:30px">			

		</div>

		<div class="map loading">
			<div class="map__nav">
				<a href="#noticias" class="button button--category button--noticias active"></a>
				<a href="#reportes" class="button button--category button--reportes active"></a>
				<a href="#eventos" class="button button--category button--eventos active"></a>
			</div>
			<div id="map"></div>
		</div>

	</div>


	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
	<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC8oXiSBU_IwCglfAkh8b-FnnesQ7qxoyY&callback=initMap"></script>
	<script src="js/bootstrap.min.js"></script>
	<!-- <script src="js/map-profile.js"></script> -->
	<script src="js/script-profile.js"></script>
	<script>
	$(function() {

		function urlify(text) {
			var urlRegex = /(https?:\/\/[^\s]+)/g;
			return text.replace(urlRegex, function(url) {
				return '<a href="' + url + '">' + url + '</a>';
			})
		}

		var bio = $('.profile__bio');
		var bioText = bio.text();

		bio.html(urlify(bioText));

	});
	</script>

</body>

</html>

<?php

function error404() {
	http_response_code(404);
	include('my_404.php');
	die();
}

?>