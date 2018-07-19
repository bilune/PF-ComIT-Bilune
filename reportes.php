<?php
session_start();

// Si no está logueado, no puede acceder a la página
if (empty($_SESSION['loggedin'])) {
	unauthorized();
}

// Conexión con la base de datos
$mysqli = new mysqli("localhost", "root", "", "xaca");
if ($mysqli->connect_errno) {
	unauthorized();
}
$mysqli->set_charset("utf8");

if (isset($_POST['historia_id']) && isset($_POST['solucionado'])) {

	$stmt = $mysqli->prepare('UPDATE historia SET solucionado = ? WHERE historia_id = ?');
	$stmt->bind_param("ii", $solucionado, $historia_id);

	$historia_id = intval($_POST['historia_id']);
	$solucionado = boolval($_POST['solucionado']);

	echo "UPDATE historia SET solucionado = $solucionado WHERE historia_id = $historia_id";

	$stmt->execute();

}

// Recoge la información de la sesión
$loggedin = $_SESSION['loggedin'];
$username = $_SESSION['username'];

$sql = "SELECT
			historia.historia_id,
			historia.titulo,
			historia.imagen,
			historia.tipo_reporte,
			historia.descripcion,
			historia.solucionado
		FROM
			historia
		INNER JOIN usuario ON usuario.usuario_id = historia.usuario_id
		WHERE
			usuario.nombre_de_usuario = '$username' AND historia.tipo_historia = 'reportes'
		ORDER BY
			historia.solucionado
		ASC,
			historia.fecha_creacion
		DESC";

$result = $mysqli->query($sql);

// Consulta la información del usuario
$sql = "SELECT
			nombre,
			nombre_de_usuario,
			email,
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


?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Configuración</title>

	<link rel="stylesheet" href="css/bootstrap.css">
	<link rel="stylesheet" href="css/dashboard.css">
	<link href="https://fonts.googleapis.com/css?family=Poppins:700|Roboto:400,500" rel="stylesheet">
	
	<style>
		td {
			vertical-align:middle!important;
		}
	</style>

</head>
<body style="overflow:auto">
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
		$imagen_perfil = !empty($datos_usuario['imagen_perfil']) ? 'images/usuarios/'.$datos_usuario['imagen_perfil'] : 'images/custom-profile.jpg';
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
			<a class="dropdown-item" href="profile.php?username=' . $datos_usuario['nombre_de_usuario'] . '">
				<img src="icons/account.svg">
				Perfil
			</a>
			<div class="dropdown-divider"></div>
			<a class="dropdown-item" href="server/logout.php">
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

	<div class="container pt-5">
		<div class="row my-md-5">
			<h1 class="col h2 font-weight-light">Mis reportes</h1>
		</div>
		<div class="row">
		
			<table class="table">
				<thead>
					<tr>
						<th class="d-none d-md-table-cell" scope="col"></th>
						<th scope="col">Título</th>
						<th class="d-none d-md-table-cell" scope="col">Descripción</th>
						<th scope="col"></th>
					</tr>
				</thead>
				<tbody>

	<?php
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				echo '
					<tr>
						<th class="d-none d-md-table-cell">' .
						(!empty($row['imagen']) ?
						'<img class="mr-3 img-thumbnail" src="images/historias/' . $row['imagen'] . '" alt="" width="200" height="200">' : '')
						. '</th>
						<td>' . $row['titulo'] . '</td>
						<td class="d-none d-md-table-cell">' . $row['descripcion'] . '</td>
						<td>
							<form class="row" method="post" action="reportes.php">
								<input type="hidden" name="historia_id" value="' . $row['historia_id'] . '">
								<input type="hidden" name="solucionado" value="' . ($row['solucionado'] ? 0 : 1) . '">
								'.
								($row['solucionado'] ?
								'<input type="submit" class="btn btn-danger btn-sm" value="Marcar como no solucionado">' :
								'<input type="submit" class="btn btn-success btn-sm" value="Marcar como solucionado">')
								.'
								
							</form>
						</td>
					</tr>';
			}
		}
	?>


				</tbody>
			</table >
		</div>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
	<script src="js/bootstrap.min.js"></script>
</body>
</html>

<?php

function unauthorized() {
	http_response_code(401);
	header('Location: login.php');
}