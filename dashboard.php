<?php
session_start();
$loggedin = false;

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {

	$loggedin = $_SESSION['loggedin'];
	$username = $_SESSION['username'];

	$host_db = 'localhost';
	$user_db = 'root';
	$pass_db = '';
	$db_name = 'xaca';
	$tbl_name = 'usuario';
	
	$conexion = new mysqli($host_db, $user_db, $pass_db, $db_name);
	
	if ($conexion->connect_error) {
		die('La conexión falló: ' . $conexion->connect_error);
	}

	$conexion->set_charset('utf8');
	$sql = "SELECT nombre, nombre_de_usuario, imagen_perfil FROM usuario WHERE nombre_de_usuario = '$username'";

	if ($result = $conexion->query($sql)) {
		$row = $result->fetch_assoc();
	}

}

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
	<link href="https://fonts.googleapis.com/css?family=Poppins:700|Roboto:400,500" rel="stylesheet">

</head>

<body>
	<div class="app">

		<!-- Navbar -->
		<nav class="navbar navbar-expand navbar-light bg-white fixed-top">

			<!-- Navbar Logo -->
			<a class="navbar-brand nav__logo">
				<img src="icons/xaca-logo-color.svg" height="30" width="80" alt="">
			</a>

			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Alternar navegación">
				<span class="navbar-toggler-icon"></span>
			</button>

			<div class="collapse navbar-collapse" id="mainNavbar">
				<ul class="navbar-nav mr-0 mr-lg-5">
					<li class="nav-item">

						<!-- Navbar Toggle Collapse -->
						<a href="#" class="button__toggle-dashboard">
							<img class="icons nav__cards" height="30" width="30" src="icons/dashboard.svg" alt="">
							<img class="icons nav__arrow-back" height="30" width="30" src="icons/arrow-back.svg" alt="">
						</a>
					</li>
				</ul>
				<br class="d-block d-md-none">

				<!-- Navbar Autocomplete -->
				<form autocomplete="off" class="form-inline navbar-nav my-2 my-lg-0 ml-2 ml-sm-4 ml-lg-0 mr-auto">
					<img src="icons/lupa.svg" class="nav__search-icon" width="24" height="24" alt="">
					<input class="form-control autocomplete pl-5 mr-sm-2" type="search" placeholder="Buscá tu barrio" aria-label="Buscá tu barrio">
				</form>

			<?php
				if (isset($loggedin) && $loggedin) {
					$imagen_perfil = !empty($row['imagen_perfil']) ? 'images/usuarios/' . $row['imagen_perfil'] : 'images/custom-profile.jpg';
					echo '
				<li class="nav-item dropdown ml-auto">
					<a class="dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<img class="rounded-circle" src="' . $imagen_perfil . '" width="40" height="40">
					</a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdown">
						<a class="dropdown-item" href="profile.php?username=' . $row['nombre_de_usuario'] . '">
							<img src="icons/account.svg">
							Perfil
						</a>
						<a class="dropdown-item" href="setup.php">
							<img src="icons/settings.svg">
							Configuración
						</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item" href="server/logout.php?return='. base64_encode('http://localhost/xaca/dashboard.php') .'">
							<img src="icons/logout.svg">
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
			</div>
			
		</nav>


		<div class="dashboard py-5 px-0 px-sm-5">

		<?php
			if ($loggedin) {
				include('post-form.php');
			}

		?>

			<div class="text-center mt-5 pt-5 d-flex flex-column justify-content-center align-items-center dashboard__no-barrio">
				<img class="mx-auto d-block" src="icons/favorite.svg" width="60" height="60" alt="No stories found emoji">
				<span class="h4 text-muted font-weight-light mx-5 mt-3">Seleccioná tu barrio para ver <br class="d-none d-xl-block"> las historias más cercanas.</span>
				<div class="m-3">
					<a href="#" class="btn btn-primary button__focus-search m-2">Elegir mi ubicación</a>
					<a href="#" class="btn btn-secondary button__toggle-dashboard m-2">Navegar en el mapa</a>
				</div>
			</div>

			<div class="text-center mt-5 pt-5 d-none flex-column justify-content-center align-items-center dashboard__no-stories">
				<img class="mx-auto d-block" src="icons/rich.svg" width="60" height="60" alt="No stories found emoji">
				<span class="h4 text-muted font-weight-light mx-5 mt-3">No encontramos historias en tu barrio. <br class="d-none d-xl-block"> ¡Sé el primero en publicar!</span>
				<div class="m-3">
					<a href="#" class="btn btn-primary button__focus-search m-2">Elegir otra ubicación</a>
				</div>
			</div>

			<div class="dashboard__ubicacion mt-4 mb-3 mx-3 d-none">
				Estás viendo historias cercanas a <strong></strong>
			</div>
			<div class="dashboard__stories"></div>
			<img class="loader mt-5 mx-auto d-none dashboard__loader" src="icons/loader.gif" style="height:  30px;width:  30px;">

		</div>

		<div class="map loading">
			<div class="map__nav">
				<a href="#noticias" class="button button--category button--noticias active"></a>
				<a href="#reportes" class="button button--category button--reportes active"></a>
				<a href="#eventos" class="button button--category button--eventos active"></a>
			</div>
			<div class="map__select-point d-none">
				Hacé click en el mapa con la posición deseada
				<a class="btn btn-primary mt-2 map__select-point__volver d-inline-block d-lg-none">Elegir ubicación</a>
			</div>
			<div id="map"></div>
		</div>

	</div>


	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/locale/es.js"></script>
	<script src="js/script.js"></script>
	<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC8oXiSBU_IwCglfAkh8b-FnnesQ7qxoyY&callback=initMap"></script>

</body>

</html>