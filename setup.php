<?php
session_start();

// Si no está logueado, no puede acceder a la página
if (empty($_SESSION['loggedin'])) {
	error404();
}

// Conexión con la base de datos
$mysqli = new mysqli("localhost", "root", "", "xaca");
if ($mysqli->connect_errno) {
	error404();
}
$mysqli->set_charset("utf8");

// Recoge la información de la sesión
$loggedin = $_SESSION['loggedin'];
$username = $_SESSION['username'];


// Procesa primer form (email y nombre)
if (!empty($_POST['email']) && !empty($_POST['nombre'])) {
	$email = $_POST['email'];
	$nombre = $_POST['nombre'];
	$datos_modificados = $mysqli->query("UPDATE usuario SET email = '$email', nombre = '$nombre' WHERE nombre_de_usuario = '$username'");
}

// Procesa segundo form (aparciencia)
if (!empty($_FILES['imagen-perfil']) && $_FILES['imagen-perfil']['size'] > 0) {

	$dir_subida = 'images/usuarios/';
	$ext = pathinfo($_FILES['imagen-perfil']['name'], PATHINFO_EXTENSION);

	$file_name = uniqid('profile_img_') .'.'. $ext;
	$fichero_subido = $dir_subida . $file_name;
	if (!move_uploaded_file($_FILES['imagen-perfil']['tmp_name'], $fichero_subido)) {
		$apariencia_modificada = false;
	}

	$apariencia_modificada = $mysqli->query("UPDATE usuario SET imagen_perfil = '$file_name' WHERE nombre_de_usuario = '$username'");
}

if (!empty($_FILES['imagen-portada']) && $_FILES['imagen-portada']['size'] > 0) {

	$dir_subida = 'images/usuarios/';
	$ext = pathinfo($_FILES['imagen-portada']['name'], PATHINFO_EXTENSION);

	$file_name = uniqid('portada_img_') .'.'. $ext;
	$fichero_subido = $dir_subida . $file_name;
	if (!move_uploaded_file($_FILES['imagen-portada']['tmp_name'], $fichero_subido)) {
		$apariencia_modificada = false;
	}

	$apariencia_modificada = $mysqli->query("UPDATE usuario SET imagen_portada = '$file_name' WHERE nombre_de_usuario = '$username'");

}

if (!empty($_POST['biografia']) && !empty($_POST['biografia'])) {
	$biografia = $_POST['biografia'];
	$apariencia_modificada = $mysqli->query("UPDATE usuario SET biografia = '$biografia' WHERE nombre_de_usuario = '$username'");
}

// Procesa tercer form (contraseña)
if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['new_password2'])) {
	$current = $_POST['current_password'];
	$new_pass = $_POST['new_password'];
	$new_pass2 = $_POST['new_password2'];

	$result = $mysqli->query("SELECT * FROM usuario WHERE nombre_de_usuario = '$username'");
	$row = $result->fetch_assoc();
	if (password_verify($current, $row['contraseña']) && $new_pass == $new_pass2) {
		$hash = password_hash($new_pass, PASSWORD_BCRYPT);
		$result = $mysqli->query("UPDATE usuario SET contraseña = '$hash' WHERE nombre_de_usuario = '$username'");

		if ($result) {
			$pw_modificada = true;
			$pw_message = "La contraseña se modificó correctamente.";	
		} else {
			$pw_modificada = false;
			$pw_message = "No se pudo modificar la contraseña. Intente nuevamente.";	
		}
	} else {
		$pw_modificada = false;
		$pw_message = "La contraseña no es correcta.";
	}
}

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
		$imagen_perfil = !empty($row['imagen_perfil']) ? $row['imagen_perfil'] : 'images/custom-profile.jpg';
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
		<div class="row my-5">
			<h1 class="col h2 font-weight-light">Configurá tu cuenta</h1>
		</div>
		<hr>

		<!-- Profile -->
		<div class="row my-5">
			<div class="col-12 col-md-4 mb-3">
				<h2 class="h5 font-weight-light text-muted">Datos de usuario</h2>
				<?php
				if (!empty($datos_modificados)) {
					echo '<small class="text-success">Los datos fueron modificados correctamente.</small>';
				}
				?>
			</div>
			<div class="col-12 col-md-8">
				<form action="setup.php" method="post">
					<div class="form-group">
						<span for="usuario">Usuario</span>
						<span class="font-weight-bold"><?php echo $datos_usuario['nombre_de_usuario']; ?></span>
					</div>
					<div class="form-group">
						<label for="email">Correo electrónico</label>
						<input type="email" class="form-control" id="email" name="email" value="<?php echo $datos_usuario['email']; ?>">
					</div>
					<div class="form-group">
						<label for="nombre">Nombre</label>
						<input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $datos_usuario['nombre']; ?>">
					</div>
					<input type="submit" class="btn btn-primary ml-auto d-block" value="Guardar">
				</form>
			</div>
		</div>

		<hr>
		<!-- Apariencia -->
		<div class="row my-5">
			<div class="col-12 col-md-4 mb-3">
				<h2 class="h5 font-weight-light text-muted">Apariencia</h2>
				<?php
				if (!empty($datos_modificados)) {
					echo '<small class="text-success">Los datos fueron modificados correctamente.</small>';
				}
				?>
			</div>
			<div class="col-12 col-md-8">
				<form enctype="multipart/form-data" action="setup.php" method="post">
					<div class="row">
						<div class="form-group col-12 col-lg-6">
							<span for="imagen-perfil">Imagen de perfil</span><br>
						<?php
						if (!empty($datos_usuario['imagen_perfil'])) {
							echo '
							<img src="images/usuarios/'.$datos_usuario['imagen_perfil'].'" alt="Imagen de perfil" class="img-thumbnail my-3" width="200" height="200">
							';
						} else {
							echo '<span>Aún no agregaste una imagen de perfil.</span>';
						}
						?>
							<input type="file" class="form-control-file" id="imagen-perfil" accept="image/*" name="imagen-perfil">
						</div>

						<hr>
						<div class="form-group col-12 col-lg-6">
							<span for="imagen-portada">Imagen de portada</span><br>
						<?php
						if (!empty($datos_usuario['imagen_portada'])) {
							echo '
							<img src="images/usuarios/'.$datos_usuario['imagen_portada'].'" alt="Imagen de portada" class="img-thumbnail my-3" style="max-height:200px">
							';
						} else {
							echo '<small>Aún no agregaste una imagen de portada.</small>';
						}
						?>
							<input type="file" class="form-control-file" id="imagen-portada" accept="image/*" name="imagen-portada">
						</div>
					</div>

					<hr>
					<div class="form-group">
						<label for="biografia">Biografía</label>
						<textarea type="text" class="form-control" id="biografia" rows="4" name="biografia" maxlength="240"><?php echo $datos_usuario['biografia']; ?></textarea>
					</div>
					<input type="submit" class="btn btn-primary ml-auto d-block" value="Guardar">
				</form>
			</div>
		</div>

		<hr>
		<!-- Password -->
		<div class="row my-5">
			<div class="col-12 col-md-4">
				<h2 class="h5 font-weight-light text-muted">Contraseña</h2>
				<?php
				if (isset($pw_modificada)) {
					echo '<small class="' . ($pw_modificada ? 'text-success' : 'text-danger') . '">'. $pw_message .'</small>';
				}
				?>
			</div>
			<div class="col-12 col-md-8">
				<form action="setup.php" method="post">
					<div class="form-group">
						<label for="current_password">Contraseña actual</label>
						<input type="password" class="form-control" id="current_password" name="current_password">
					</div>
					<div class="form-group">
						<label for="new_password">Contraseña nueva</label>
						<input type="password" class="form-control" id="new_password" name="new_password">
					</div>
					<div class="form-group">
						<label for="new_password2">Confirmar nueva contraseña</label>
						<input type="password" class="form-control" id="new_password2" name="new_password2">
					</div>
					<input type="submit" class="btn btn-primary ml-auto d-block" value="Guardar">
				</form>
			</div>
		</div>

	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
	<script src="js/bootstrap.min.js"></script>
</body>
</html>

<?php

function error404() {
	http_response_code(404);
	include('my_404.php');
	die();
}

?>