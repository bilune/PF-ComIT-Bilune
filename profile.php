<?php

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
			biografia,
			imagen_perfil,
			imagen_portada,
			color
		FROM
			usuario
		WHERE
			usuario_id = 1";

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
		<nav class="navbar navbar-expand navbar-light fixed-top" style="background-color: <?php echo $datos_usuario['color']; ?>">

			<!-- Navbar Logo -->
			<a class="navbar-brand nav__logo">
				<img src="icons/xaca-logo-<?php echo color_contrast($datos_usuario['color']) == '#fff' ? 'b' : 'w'; ?>.svg" height="30" width="80" alt="Logo xacá">
			</a>

			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Alternar navegación">
				<span class="navbar-toggler-icon"></span>
			</button>

			<div class="collapse navbar-collapse" id="mainNavbar">
				<button class="btn btn-primary ml-auto">
					<span class="d-inline d-lg-none">Entrar</span>
					<span class="d-none d-lg-inline">Iniciar sesión</span>
				</button>
			</div>
			
		</nav>


		<div class="dashboard py-5">
			
			<!-- Profile -->
		<?php
		echo 
			'<div class="profile bg-white px-3 pb-2 pb-lg-4 mb-4">
				<figure class="profile__header">
					<img src="' . $datos_usuario['imagen_portada'] . '" alt="Imagen de portada de ' . $datos_usuario['nombre'] . '">
				</figure>
				<img src="' . $datos_usuario['imagen_perfil'] . '" class="profile__picture img-thumbnail" alt="Imagen de perfil de ' . $datos_usuario['nombre'] . '">

				<h1 class="profile__name h3 mb-0">' . $datos_usuario['nombre'] . '</h1>
				<div class="profile__username text-muted">@' . $username . '</div>
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

function color_contrast($color) {
    $color = str_replace('#', '', $color);
	if (strlen($color) != 6){ return '000000'; }

	// Se invierte y se convierte a rgb
    $rgb = array(
		'r' => 255 - hexdec(substr($color,0,2)),
		'g' => 255 - hexdec(substr($color,2,2)),
		'b' => 255 - hexdec(substr($color,4,2))
	);
	
	$intensity = floor(0.3 * $rgb['r'] + 0.59 * $rgb['g'] + 0.11 * $rgb['b']);
	if ($intensity > 127.5) {
		return '#000';
	} else {
		return '#fff';
	}
}

?>