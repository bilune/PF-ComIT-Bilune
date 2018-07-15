<!-- Post form -->
<div class="post-story mx-0 mx-md-5 mx-lg-2 mx-xl-3 p-4 px-md-5 px-lg-4 px-xl-5 mt-sm-4 mt-md-0">

	<!-- New post categories -->
	<form>
		<div class="form-group row mb-0">
			<label class="col-12 col-form-label text-center px-0">
				<span class="h4 text-muted font-weight-light mx-0 mx-md-5 mx-lg-0 mx-xl-5">¿Qué está pasando cerca tuyo?</span>
			</label>
			<div class="col-12 text-center p-0 p-sm-auto">
				<a href="#noticia" class="btn btn-secondary post-story__button px-3 px-sm-4 my-1 mr-1 mr-sm-2">Noticia</a>
				<a href="#evento" class="btn btn-secondary post-story__button px-3 px-sm-4  my-1 mr-1 mr-sm-2">Evento</a>
				<a href="#reporte" class="btn btn-secondary post-story__button px-3 px-sm-4 my-1 mr-1 mr-sm-2">Reporte</a>

			</div>
		</div>
	</form>

	<!-- New post form noticia -->
	<form class="row post-story__form post-story__form--noticia d-none">
		<hr class="w-100">

		<div class="form-group col-12 my-1">
			<label for="url" class="col-form-label mx-2 pb-0">Enlace</label>
			<input type="text" class="form-control" id="url" name="url" placeholder="Pegá la URL de la noticia que querés compartir">
		</div>
		<div class="col-12 preview mt-3">
			<img src="icons/loader.gif" width="30" height="30" class="preview__loader d-none mx-auto" alt="">
			<div></div>
		</div>

		<div class="form-group col-12 row mt-5 mb-3 d-none">
			<div class="col-12 text-center mb-2">Elegí en qué barrio/s querés que se muestre esta historia</div>
			<div class="col-12 text-center post-story__form--barrios"></div>
		</div>

		<hr class="w-100 mt-4">

		<div class="col-12 d-flex justify-content-between">
			<input type="submit" value="Cancelar" class="post-story__cancel-button btn btn-outline-danger mr-auto">
			<input type="submit" value="Previsualizar" class="post-story__submit-button btn btn-primary">
		</div>
		
	</form>

	<!-- New post form evento -->
	<form class="row post-story__form post-story__form--evento d-none">
		<hr class="w-100">
		
		<!-- Título Evento -->
		<div class="form-group col-12 my-1">
			<label for="titulo-evento" class="col-form-label mx-2 pb-0">Título</label>
			<input type="text" class="form-control" id="titulo-evento" name="titulo" maxlength="100">
			<small class="form-text text-muted text-right mx-2"></small>
		</div>

		<!-- Descripción evento -->
		<div class="form-group col-12 my-1">
			<label for="descripcion-evento" class="col-form-label mx-2 pb-0">Descripción <small class="text-muted font-italic">opcional</small></label>
			<textarea type="file" class="form-control" id="descripcion-evento" name="descripcion" maxlength="240"></textarea>
			<small class="form-text text-muted text-right mx-2"></small>
		</div>

		<!-- Fecha evento -->
		<div class="form-group col-12 col-sm-6 col-lg-12 col-xl-6 my-1">
			<label for="fecha-evento" class="col-form-label mx-2 pb-0">Fecha</label>
			<input type="date" class="form-control" id="fecha-evento" name="fecha">
			<small class="form-text text-muted text-right"></small>
		</div>

		<!-- Hora evento -->
		<div class="form-group col-12 col-sm-6 col-lg-12 col-xl-6 my-1">
			<label for="hora-evento" class="col-form-label mx-2 pb-0">Hora</label>
			<input type="time" class="form-control" id="hora-evento" name="hora">
			<small class="form-text text-muted text-right"></small>
		</div>

		<!-- Imagen Evento -->
		<div class="form-group d-flex justify-content-start align-items-center col-12 my-1">
			<label for="imagen-evento" class="col-form-label mx-2 pb-0">Imagen <small class="text-muted font-italic">opcional</small></label>
			<input type="file" class="form-control-file" name="imagen" id="imagen-evento">
		</div>

		<!-- Elegir barrio evento -->
		<div class="form-group col-12 my-2 d-none">
			<label class="col-form-label mx-2 pb-0">Elegir barrio/s donde mostrar la noticia</label>
			<div class="post-story__form--barrios mt-2"></div>
		</div>

		<hr class="w-100 mt-4">

		<!-- Botones evento -->
		<div class="col-12 d-flex justify-content-between">
			<input type="submit" value="Cancelar" class="post-story__cancel-button btn btn-outline-danger">
			<input type="submit" value="Ubicar" class="btn btn-primary post-story__submit-button">		
		</div>
	</form>


	<!-- New post form reporte -->
	<form class="row post-story__form post-story__form--reporte d-none">
		<hr class="w-100">

		<!-- Título reporte -->
		<div class="form-group col-12 my-1">
			<label for="titulo-reporte" class="col-form-label mx-2 pb-0">Título</label>
			<input type="text" class="form-control" id="titulo-reporte" name="titulo" maxlength="100">
			<small class="form-text text-muted text-right"></small>
		</div>

		<!-- Descripción reporte -->
		<div class="form-group col-12 my-1">
			<label for="descripcion-reporte" class="col-form-label mx-2 pb-0">Descripción <small class="text-muted font-italic">opcional</small></label>
			<textarea type="file" class="form-control" id="descripcion-reporte" name="descripcion" maxlength="240"></textarea>
			<small class="form-text text-muted text-right"></small>
		</div>

		<!-- Tipo reporte -->
		<div class="form-group col-12 my-1">
			<label for="tipo-reporte" class="col-form-label mx-2 pb-0">Tipo de reporte</label>
			<select name="tipo-reporte" class="form-control" id="tipo-reporte" name="tipo">
				<option>Servicios: agua</option>
				<option>Servicios: luz</option>
				<option>Servicios: gas</option>
				<option>Accidente de tránsito</option>
				<option>Obra sin terminar</option>
				<option>Otro</option>
			</select>
			<small class="form-text text-muted text-right"></small>
		</div>

		<!-- Imagen reporte -->
		<div class="form-group col-12 d-flex justify-content-start align-items-center my-1">
			<label for="imagen-reporte" class="col-form-label mx-2 pb-0">Imagen <small class="text-muted font-italic">opcional</small></label>
			<input type="file" class="form-control-file" id="imagen-reporte" name="imagen">
		</div>

		<!-- Elegir barrio reporte -->
		<div class="form-group col-12 my-2 d-none">
			<label class="col-form-label mx-2 pb-0">Elegir barrio/s donde mostrar la noticia</label>
			<div class="post-story__form--barrios mt-2"></div>
		</div>
		
		<hr class="w-100 mt-4">

		<!-- Botones evento -->
		<div class="col-12 d-flex justify-content-between">
			<input type="submit" value="Cancelar" class="post-story__cancel-button btn btn-outline-danger">
			<input type="submit" value="Ubicar" class="btn btn-primary post-story__submit-button">
		</div>
	</form>
</div>

<div class="post-story__result mx-0 mx-md-5 mx-lg-2 mx-xl-3 mb-3 py-2 mt-sm-4 mt-md-0 text-center h6 font-weight-light text-white">
</div>