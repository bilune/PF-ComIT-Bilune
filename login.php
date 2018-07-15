<?php

if (isset($_POST['username']) && isset($_POST['password'])) {

	session_start();

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
	
	$username = $_POST['username'];
	$password = $_POST['password'];
	
	$sql = "SELECT * FROM $tbl_name WHERE nombre_de_usuario = '$username' OR email = '$username'";
	
	$result = $conexion->query($sql);
	
	if ($result && $result->num_rows > 0) {
		$row = $result->fetch_assoc();
		if (password_verify($password, $row['contraseña'])) {
			$_SESSION['loggedin'] = true;
			$_SESSION['username'] = $username;
			$_SESSION['usuario_id'] = $row['usuario_id'];
			$_SESSION['nombre'] = $row['nombre'];
			$_SESSION['start'] = time();
			$_SESSION['expire'] = $_SESSION['start'] + (5 * 60);
		
			header('Location: http://localhost/xaca/dashboard.php');
		} else {
			$fallo = true;
		}
	} else {
		$fallo = true;
	}
	
	$conexion->close();
	
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Document</title>

	<link rel="stylesheet" href="css/bootstrap.css">
	<link rel="stylesheet" href="css/login.css">
	<link href="https://fonts.googleapis.com/css?family=Poppins:700|Roboto:400,500" rel="stylesheet">

</head>
<body>
	
	<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
		<a class="navbar-brand nav__logo">
			<img src="icons/xaca-logo-color.svg" height="30" width="80" alt="">
		</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Alternar navegación">
			<span class="navbar-toggler-icon"></span>
		</button>

	</nav>

	<div class="container py-5">
		<div class="row my-5">
			<div class="col-12 col-md-5">
				<h3 class="my-3">Ingresá a tu cuenta</h3>
				<form class="mb-3" method="post" action="login.php">
					<div class="form-group">
						<label for="username">Usuario o email</label>
						<input type="text" class="form-control" id="username" name="username">
					</div>
					<div class="form-group">
						<label for="password">Contraseña</label>
						<input type="password" class="form-control" id="password" name="password">
					</div>
					<div class="form-group form-check">
						<input type="checkbox" class="form-check-input" id="recordarme" name="recordarme">
						<label class="form-check-label" for="recordarme">Recordarme</label>
					</div>
					<button type="submit" class="btn btn-secondary px-5">Entrar</button>

				<?php
					if (isset($fallo) && $fallo) {
						echo '<span class="text-white">Usuario o contraseña incorrectos.</span>';
					}
					
				?>
				</form>

				<a href="register.php" class="text-white">Aún no estoy registrado.</a>

			</div>
			<div class="col-12 d-hide col-md-6 offset-md-1 text-right d-md-flex flex-column justify-content-center">
				<span class="slogan">
					Está pasando cerca tuyo.
				</span>
			</div>
		</div>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>

</body>
</html>