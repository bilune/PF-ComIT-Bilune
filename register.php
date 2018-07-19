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

	<style>
	.is-invalid {
		background-image: url(icons/close.svg);
	}
	.is-valid {
		background-image: url(icons/tick.svg);
	}
	.is-loading {
		background-image: url(icons/loader.gif);
	}

	.form-control {
		background-size: 20px;
		background-repeat: no-repeat;
		background-position: 98%;
	}
	</style>
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
		<div class="row">
			<div class="col-12 col-md-5 px-4 px-md-0">
				<h3 class="my-3">¡Te damos la bienvenida!</h3>
				<form class="mb-3" action="server/registrar-usuario.php" method="post">

					<!-- Nombre de usuario -->
					<div class="form-group">
						<label for="nombre_de_usuario">Usuario</label>
						<input type="text" class="form-control" id="username" name="usuario" required>
						<div class="invalid-feedback text-white text-right">
							El nombre de usuario no está disponible
						</div>
					</div>

					<!-- Nombre -->
					<div class="form-group">
						<label for="nombre">Nombre</label>
						<input type="text" class="form-control" id="nombre" name="nombre" required>
					</div>

					<!-- Email -->
					<div class="form-group">
						<label for="email">Email</label>
						<input type="email" class="form-control" id="email" name="email" required>
					</div>

					<!-- Password -->
					<div class="form-group">
						<label for="password">Contraseña</label>
						<input type="password" class="form-control" id="password" name="password" required>
					</div>

					<!-- Password again -->
					<div class="form-group">
						<label for="password_again">Ingresá tu contraseña nuevamente</label>
						<input type="password" class="form-control" id="password_again" name="password_again" required>
						<div class="invalid-feedback text-white text-right">
							La contraseña no coincide.
						</div>

					</div>

					<button type="submit" id="submit" class="btn btn-secondary px-5">Registrarme</button>
				</form>

				<a href="login.php" class="text-white">Ya estoy registrado. Iniciar sesión.</a>

			</div>
			<div class="col-12 col-md-6 order-first order-md-last offset-md-1 text-center text-md-right d-md-flex flex-column justify-content-center mb-5 mb-md-0">
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

	<script>
	var register = (function() {

		var elems;

		var validateUsername = function() {
			var $this = elems.username;
			var value = $this.val();
			$this.addClass('is-loading');

			$.getJSON('http://localhost/xaca/server/api/usuario.php', {
				query: 'isValid',
				username: value
			}, function(result, status) {
				if (status === 'error') {
					// To do: error handling
					return false;
				}

				$this.removeClass('is-loading');
				if (result.data.isValid) {
					$this
						.addClass('is-valid')
						.removeClass('is-invalid')
						.get(0).setCustomValidity('');
				} else {
					$this
						.addClass('is-invalid')
						.removeClass('is-valid')
						.get(0).setCustomValidity('Tenés que elegir otro nombre de usuario.');
				}
			});

		}

		var validatePasswords = function() {
			console.log(elems.secondPassword.val());

			if (elems.secondPassword.val() !== '' && 
			elems.password.val() !== elems.secondPassword.val()) {

				elems.secondPassword
					.addClass('is-invalid')
					.removeClass('is-valid')
					.get(0).setCustomValidity('Las contraseñas no coinciden.');

			} else if (elems.secondPassword.val() !== '') {
				elems.secondPassword
					.addClass('is-valid')
					.removeClass('is-invalid')
					.get(0).setCustomValidity('');

			}
		}

		var enlazarElementos = function() {
			var self = {};

			self.username = $('#username');
			self.password = $('#password');
			self.secondPassword = $('#password_again');

			return self;
		}

		var enlazarEventos = function() {

			elems.username.change(validateUsername);
			elems.password.change(validatePasswords);
			elems.secondPassword.change(validatePasswords);
		}

		var init = function() {
			elems = enlazarElementos();
			enlazarEventos();
		}

		return {
			init: init
		}
	})();


	$(register.init);
	</script>
</body>
</html>