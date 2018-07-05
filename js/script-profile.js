var App = (function() {

	// Objeto que contiene todos los controles de jQuery
	var elems = {};
	
	var markersFilter = {
		noticias: true,
		eventos: true,
		reportes: true
	};

	var loadingMoreStories = false;

	// --------DASHBOARD--------

	// Pide al servidor y carga las historias correspondientes al barrio seleccionado
	var dashboardLoadStories = function(id, name) {

		// Elimina el mensaje que se muestra cuando no hay barrio seleccionado
		elems.noNeighborhood.removeClass('d-flex').addClass('d-none');

		var dashboard = elems.dashboard;
		dashboard.addClass('loading');

		$.getJSON('http://localhost/api/historias.json', {}, function(result, status) {
			if (status === 'error') {
				// TO DO: Handle error
			} else {

				dashboard.append(
					$('<div></div>')
						.addClass('mt-4 mb-3 mx-3')
						.html('Estás viendo historias de <strong>'+name+'</strong>')
				);

				result.data.forEach(function(historia) {
					var card = dashboard
						.removeClass('loading')
						.append(historia.html)
						.find('.card:last');

					// Acorta los textos de las historias y agrega un botón para ver más
					shortenDescriptions(card);

					// Eventos para resaltar marcador cuando se hace 'hover' sobre una historia
					card
						.mouseenter(function() {
							markers[historia.id].marker.setAnimation(google.maps.Animation.BOUNCE);
						})
						.mouseleave(function() {
							markers[historia.id].marker.setAnimation(null);
						});
				});
			}
		});
	}

	var dashboardScrollBottom = function() {
		var dashboard = elems.dashboard;

		if (!loadingMoreStories && dashboard.scrollTop() + dashboard.outerHeight() > dashboard.prop('scrollHeight') - 62) {
			loadingMoreStories = true;

			setTimeout(function() {
				elems.dashboardLoader.before(
					$('<div></div>').width('100%').height('400px').css('background', '#000')
				);
				loadingMoreStories = false;
	
			}, 5000);
		}
	}

	// Acorta los textos de las historias y agrega un botón para ver más
	var shortenDescriptions = function(card) {
		var elem = card.find('.card-text');
		var text = elem.text();

		if (text.length > 140) {
			var firstPart = text.substr(0, 140);
			firstPart = firstPart.substr(0, Math.min(firstPart.length, firstPart.lastIndexOf(" "))) + '...';
	
			elem.html('<span>' + firstPart + '</span><a class="ml-2" href="#">Ver más</a>');
				
			elem
				.children('a')
				.click(function() {
					var a = $(this);
					var span = elem.children('span');
					span.empty();
					if (a.text() === 'Ver más') {
						span.text(text);
						a.text('Ver menos');
					} else {
						span.text(firstPart);
						a.text('Ver más');
					}
			});
		}
	}

	// --------MAP--------

	var mapFilterMarkers = function(e) {
		if (e) e.preventDefault();

		var $this = $(this);
		var categoria = $this.attr('href').substr(1);

		if (markersFilter[categoria]) {
			$this.removeClass('active');
			markersFilter[categoria] = false;
		} else {
			$this.addClass('active');
			markersFilter[categoria] = true;
		}

		Object.values(markers).forEach(function(markerObj) {

			if (markersFilter[markerObj.categoria]) {
				markerObj.marker.setMap(map);
			} else {
				markerObj.marker.setMap(null);
			}
		});
	}

	// --------GENERAL--------

	// Función que selecciona todos los elementos necesarios y los retorna en un objeto
	var enlazarElems = function () {
		var self = {};
		
		// Dashboard
		self.dashboard = $('.dashboard');
		self.dashboardLoader = $('.dashboard .loader');

		// Map
		self.mapFilterButtons = $('.button--category');

		return self;

	};
	
	// Función que enlaza las funciones con los elementos a través de eventos
	var enlazarFunciones = function() {

		elems.mapFilterButtons.on('click', mapFilterMarkers);

		elems.dashboard.on('scroll', dashboardScrollBottom);

    };

	var init = function() {

		// Asigna a elem los elementos
		elems = enlazarElems();
		enlazarFunciones();
		
	}

	return {
		init: init
	};


})();

$(document).ready(App.init);