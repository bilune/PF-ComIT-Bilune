var App = (function() {

	// Objeto que contiene todos los controles de jQuery
	var elems = {};
	
	var autocompleteCache = {};
	var polygonNbh;

	var map = null;
	var markers = {};
	var infoWindow = null;

	var markersFilter = {
		noticias: true,
		eventos: true,
		reportes: true
	};

	var mapBounds = null;

	var selectedBarrioID = 0;
	var lastStoryDateTime = null;

	var loadingMoreStories = false;

	var dashboardExpanded = false;

	// --------DASHBOARD--------

	// Abre o cierra la sección 'Dashboard'
	var dashboardToggle = function (e) {
		if (e) e.preventDefault();

		elems.app.toggleClass('expanded');
		var navValue = localStorage.getItem('nav') === 'expanded' ? 'collapsed' : 'expanded';
		dashboardExpanded = navValue === 'expanded';
		localStorage.setItem('nav', navValue);

		if (navValue === 'expanded' && typeof infoWindow !== 'null') {
			infoWindow.close();
		}
	}

	// Abre la sección 'Dashboard'
	var dashboardOpen = function(e) {
		if (e) e.preventDefault();

		dashboardExpanded = true;
		elems.app.addClass('expanded');
		localStorage.setItem('nav', 'expanded');
	}

	// Abre la sección 'Dashboard'
	var dashboardClose = function(e) {
		if (e) e.preventDefault();
		
		dashboardExpanded = false;
		elems.app.removeClass('expanded');
		localStorage.setItem('nav', 'collapsed');
	}

	// Pide al servidor y carga las historias correspondientes al barrio seleccionado
	var dashboardLoadStories = function(id, callback) {

		// Elimina el mensaje que se muestra cuando no hay barrio seleccionado o no hay historias en el barrio elegido
		elems.noNeighborhood.removeClass('d-flex').addClass('d-none');
		elems.noStories.removeClass('d-flex').addClass('d-none');

		var dashboard = elems.dashboard;
		dashboard.addClass('loading');

		$.getJSON('server/api/historia.php', {
			query: 'historias',
			barrio: id
		}, function(result, status) {
			if (status === 'error') {
				// TO DO: Handle error
			} else {

				dashboard.removeClass('loading');
				elems.stories.empty();
				mapBounds = new google.maps.LatLngBounds();

				result.data.forEach(function(historia) {

					mapBounds.extend({
						lat: historia.geometry.lat,
						lng: historia.geometry.lng
					});

					lastStoryDateTime = historia.fecha_creacion;

					var card = elems.stories
						.append(historia.html)
						.find('.card:last')
						.addClass('mx-3 mx-md-5 mx-lg-2 mx-xl-3')

						// Eventos para resaltar marcador cuando se hace 'hover' sobre una historia
						.on('mouseenter mouseleave', function(e) {
							var animation = e.type === 'mouseenter' ? google.maps.Animation.BOUNCE : null;
							var id = $(this).attr('id');

							if (typeof markers[id] !== 'undefined') {
								markers[id].marker.setAnimation(animation);
							}
						})

						.find('.card__timeago')
						.text(moment.unix(historia.fecha_creacion).fromNow());

					// Acorta los textos de las historias y agrega un botón para ver más
					shortenDescriptions(card);

				});

				callback(result.data.length === 0);
				mapSetMarkers(result.data);
			}
		});
	}

	var dashboardLoadMoreStories = function(callback) {

		$.getJSON('server/api/historia.php', {
			query: 'historias',
			barrio: selectedBarrioID,
			antes_de: lastStoryDateTime
		}, function(result, status) {
			if (status !== 'error') {

				callback(result.data.length === 0);

				result.data.forEach(function(historia) {

					mapBounds.extend({
						lat: historia.geometry.lat,
						lng: historia.geometry.lng
					});

					lastStoryDateTime = historia.fecha_creacion;

					var card = elems.stories
						.append(historia.html)
						.find('.card:last')
						.addClass('mx-3 mx-md-5 mx-lg-2 mx-xl-3')

						// Eventos para resaltar marcador cuando se hace 'hover' sobre una historia
						.on('mouseenter mouseleave', function(e) {
							var animation = e.type === 'mouseenter' ? google.maps.Animation.BOUNCE : null;
							var id = $(this).attr('id');

							if (typeof markers[id] !== 'undefined') {
								markers[id].marker.setAnimation(animation);
							}
						})

						.find('.card__timeago')
						.text(moment.unix(historia.fecha_creacion).fromNow());

					// Acorta los textos de las historias y agrega un botón para ver más
					shortenDescriptions(card);

				});
				
				mapSetMarkers(result.data);

				// Enfoca el mapa
				map.fitBounds(mapBounds);
				
			} else {
				// TO DO: error handling
			}

		});
	}

	var dashboardScrollBottom = function() {
		var dashboard = elems.dashboard;

		if (!loadingMoreStories && dashboard.scrollTop() + dashboard.outerHeight() === dashboard.prop('scrollHeight')) {
			elems.dashboardLoader.removeClass('d-none').addClass('d-block');
			loadingMoreStories = true;

			dashboardLoadMoreStories(function(noMoreStories) {

				if (noMoreStories) {
					dashboard.unbind('scroll');
				}

				loadingMoreStories = false;
				elems.dashboardLoader.addClass('d-none').removeClass('d-block');
			});
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

	// Muestra u oculta los formularios para publicar una nueva historia
	var postStoryToggleForm = function(e) {
		if (e) e.preventDefault();
		$button = $(this);

		var href = $button.attr('href').substr(1);
		elems.postStoryButtons.removeClass('active');

		elems.postStoryForms.each(function() {
			$form = $(this);
			if ($form.hasClass('post-story__form--'+href) && $form.hasClass('d-none')) {
				$form.removeClass('d-none');
				$button.addClass('active');
			} else {
				$form.addClass('d-none');
			}
		});
	}

	var postStoryCancel = function(e) {
		if (e) e.preventDefault();

		elems.postStoryButtons.removeClass('active');

		elems.postStoryForms
			.addClass('d-none')
			.trigger("reset")
			.find('small.form-text')
			.empty();
	}

	var postNoticiaSubmit = function(e) {
		if (e) e.preventDefault();
			
		var $this = $(this);
		var inputUrl = elems.postNoticia.find('input#url');
		var loader = elems.postNoticia.find('.preview__loader');
		var preview = elems.postNoticia.find('.preview > div');


		var url = inputUrl.val();
		loader
			.removeClass('d-none')
			.addClass('d-block');
		preview.empty();

		$.getJSON('server/api/historia.php', {
			query: 'previsualizar',
			url: url
		}, function(result, status) {
			if (status === 'error') {
				preview.text('Algo falló.');
			} else {
				if (result.html) {
					$this
						.val('Publicar')
						.prop('disabled', true);
					preview.html(result.html);

					mapSelectPoint(elems.postNoticia, function(latLng) {
						if (latLng) {
							// TO DO: PUBLICAR INFORMACIÓN
						} else {
							// Elección de punto cancelada
							preview.empty();
							$this.val('Previsualizar').prop('disabled', false);
						}
					});
				} else {
					preview.text('No es una url válida.');
				}
			}
			loader
				.addClass('d-none')
				.removeClass('d-block');
		});
	}

	var postEventoSubmit = function(e) {
		if (e) e.preventDefault();
			
		var data = elems.postEvento.serializeArray().reduce(function(red, elem) {
			red[elem.name] = elem.value;
			return red;
		}, {});
		var $this = $(this);

		if (data.titulo !== '' && data.fecha !== '' && data.hora !== '') {
			mapSelectPoint(elems.postEvento, function(latLng) {
				if (latLng) {
					$this.val('Publicar');
					// TO DO: PUBLICAR INFORMACIÓN
				} else {
					// Elección de punto cancelada
					$this.val('Ubicar');
				}
			});
		} else {
			var elementos = elems.postEvento.find('#titulo-evento, #fecha-evento, #hora-evento');
			postShowInputErrors(elementos);

		}
	}

	var postReporteSubmit = function(e) {
		if (e) e.preventDefault();
			
		var data = elems.postReporte.serializeArray().reduce(function(red, elem) {
			red[elem.name] = elem.value;
			return red;
		}, {});

		var $this = $(this);

		if (data.titulo !== '' && data.tipo !== '') {
			mapSelectPoint(elems.postReporte, function(latLng) {
				if (latLng) {
					$this.val('Publicar');
					// TO DO: PUBLICAR INFORMACIÓN
				} else {
					// Elección de punto cancelada
					$this.val('Ubicar');
				}
			});
		} else {
			var elementos = elems.postReporte.find('#titulo-reporte, #tipo-reporte');
			postShowInputErrors(elementos);
		}
	}

	// Muestra un mensaje de error en todos los campos que sean inválidos
	var postShowInputErrors = function(elementos) {
		elementos.each(function(elem) {
			var elem = $(this);
			var inputHelp = elem.siblings('small');

			elem.on('blur', function() {
				if (elem.val() === '') {
					elem.addClass('is-invalid');
					inputHelp.html('<span class="text-danger">Debe completar este campo.</span>');
				} else {
					elem.removeClass('is-invalid');
					inputHelp.empty();

				}
			});

			inputHelp.empty();
			if (elem.val() === '') {
				elem.addClass('is-invalid');
				inputHelp.html('<span class="text-danger">Debe completar este campo.</span>');
			}
		});

	} 

	var postRemainingChars = function() {
		var $this = $(this);
		var inputLength = $this.val().length;
		var maxLength = $this.attr('maxlength');

		$this.siblings('small').html(inputLength + '/' + maxLength);

	}

	var postHideRemainingChars = function() {
		$(this).siblings('small').empty();
	}

	// Consulta los barrios más cercanos al punto y da la posibilidad al usuario de elegir en cuáles mostrar la historia
	var postSelectBarrios = function(form, lat, lng) {
		$.getJSON('server/api/barrio.php', {
			query: 'cercanos',
			lat: lat,
			lng: lng
		}, function(response, status) {
			if (status !== 'error') {
				var barrios = form.find('.post-story__form--barrios');
				barrios.empty().parent().removeClass('d-none');

				response.data.forEach(function(barrio) {
					barrios.append(
						$('<label></label>')
							.addClass('btn mx-1' + (barrio.contiene ? ' btn-secondary' : ' btn-outline-secondary'))
							.append(
								$('<input type="checkbox">')
									.addClass('d-none')
									.attr('name', barrio.id)
									.val(barrio.id)
									.prop({
										'checked': barrio.contiene,
										'disabled': barrio.contiene
									})
									.change(function(e) {
										var $this = $(this);

										if ($this.prop('checked')) {
											$this.parent().addClass('btn-secondary').removeClass('btn-outline-secondary');
										} else {
											$this.parent().removeClass('btn-secondary').addClass('btn-outline-secondary');
										}
									})

							).append(barrio.nombre)
					);
				});
				
			} else {
				// TO DO: Completar error al obtener barrios
			}
		});

	}

	// --------NAV--------

	// Enfoca el input para buscar los barrios
	var focusSearchInput = function(e) {
		if (e) e.preventDefault();
		elems.navbarSearchInput.focus();
	}

	// Inicia el script autocomplete
	var autocompleteInit = function() {
		elems.navbarSearchInput.autocomplete({
			appendTo: '.navbar',
			position: {
				my: "left top+8"
			},
			minLength: 0,
			source: function( request, response ) {
				var term = request.term;
				if (term in autocompleteCache) {
					response( autocompleteCache[ term ] );
					return;
				}
				$.getJSON( "server/api/barrio.php", {
					query: 'buscar',
					term: term
				}, function( data, status ) {
					autocompleteCache[ term ] = data.data;
					response( data.data );
				});
			},
			close: function() {
				console.log('closed');
				$this = $(this);
				setTimeout(function(){
					$this.blur();
				});
			},
			select: autocompleteSelect
		});
	}

	// Abre el autocomplete cuando se hace click en el input
	var autocompleteOpen = function(e) {
		console.log(e);
		var value = elems.navbarSearchInput.val();
		elems.navbarSearchInput.autocomplete('search', value);
	}

	// Se ejecuta cuando se selecciona una opción del autocomplete
	// Se cargan historias en 'Dashboard' y límites en el mapa
	var autocompleteSelect = function(e, ui) {
		e.preventDefault();

		elems.navbarSearchInput
			// .autocomplete('close')
			.val('');
	
		dashboardOpen();

		$.getJSON('server/api/barrio.php', {
			query: 'limites',
			id: ui.item.id
		}, function(result, status) {
			if (status === 'error') {
				// TO DO: Handle error
			} else {

				selectedBarrioID = ui.item.id;
				dashboardLoadStories(ui.item.id, function(noStories) {
					mapFitBounds(result.data.boundingBox, result.data.bounds);

					if (noStories) {
						elems.noStories.removeClass('d-none').addClass('d-flex');
						elems.ubicacionActual
							.addClass('d-none')
							.children('strong')
							.empty();
					} else {
						elems.ubicacionActual
							.removeClass('d-none')
							.children('strong')
							.text(ui.item.value);
					}
				});
				
				elems.dashboard.on('scroll', dashboardScrollBottom);

			}
		});

	}

	// --------MAP--------

	var mapLoadMarkers = function() {
		$.getJSON('server/api/historia.php', {
			query: 'markers'
		}, function(response, status) {
			if (status !== 'error') {

				infoWindow = new google.maps.InfoWindow({
					content: '<img src="icons/loader.gif" width="30" height="30" class="mx-auto my-5">'
				});

				mapSetMarkers(response.data);

			}
		});
	}

	// Ubica los marcadores en el mapa
	// arrayOfMarkers : { 'id': String , 'geometry': Object( 'lat': Number, 'lng': Number ), 'category': String }
	var mapSetMarkers = function(arrayOfMarkers) {

		arrayOfMarkers.forEach(function(marker) {

			var markerID = 'id' + marker.id;

			if (markerID in markers) {
				return false;
			}

			markers[markerID] = {
				marker: new google.maps.Marker({
					position: marker.geometry,
					map: map,
					icon: 'icons/spotlight-poi-'+marker.category+'.png'
				}),
				categoria: marker.category
			}

			markers[markerID].marker.addListener('click', function() {

				if ($('.app').hasClass('expanded')) { // Está expandido -> se enfoca la historia seleccionada

					var $card = $('#'+markerID);

					var scrollTop = $card.length ? $card.position().top : 0;
					
					$('.dashboard')
					.animate({
						scrollTop: '+=' + scrollTop
					}, 1000);

				} else { // Está contraído -> se abre una InfoWindow

					infoWindow.open(map, markers[markerID].marker);

					$.getJSON('server/api/historia.php', {
						query: 'historia',
						id: marker.id
					}, function(response, status) {
						if (status !== 'error') {
							infoWindow.setContent(response.data.html);
						}
					});
				}

			});
		});

	}

	// Enfoca y agrega los límites del barrio seleccionado en el mapa
	var mapFitBounds = function(boundingBox, nbhBounds) {

		// var bounds = new google.maps.LatLngBounds();
		boundingBox.forEach(function(val) {
			mapBounds.extend({
				lat: val[1],
				lng: val[0]
			});
		});

		// Enfoca el mapa
		map.fitBounds(mapBounds); 

		// Elimina el polígono anterior
		if (polygonNbh) polygonNbh.setMap(null); 

		// Crea el nuevo polígono y lo agrega al mapa
		polygonNbh = new google.maps.Polyline({
			clickeable: false,
			strokeColor: '#000',
			strokeOpacity: '0.3',
			strokeWeight: 0.5,
			map: map,
			icons: [{
				icon: {
					path: 'M 0,-1 0,1',
					fillOpacity: 0.5,
					scale: 3
				},
				offset: 0,
				repeat: '20px'
			}],
			path: nbhBounds.map(function(val) {
				return {
					lat: val[1],
					lng: val[0]
				};
			})
		});
		
	}

	// Permite insertar un punto en el mapa y devuelve el valor del punto si la publicación no es cancelada
	var mapSelectPoint = function(form, callback) {

		elems.mapSelectPointPopover.removeClass('d-none');
		if ($(window).width() < 992) dashboardClose();

		var marker;

		var listener = google.maps.event.addListener(map, 'click', function(e) {
			if (typeof marker !== 'undefined') {
				marker.setMap(null);
			}
			marker = new google.maps.Marker({
				position: e.latLng,
				map: map
			});
			form.find('.post-story__submit-button').prop('disabled', false);

			postSelectBarrios(form, e.latLng.lat, e.latLng.lng);
			
			if ($(window).width() < 992) dashboardOpen();

			callback(e.latLng);
		});

		elems.postStoryCancelButton
			.click(function(e) {
				e.preventDefault();

				google.maps.event.removeListener(listener);
				if (typeof marker !== 'undefined') {
					marker.setMap(null);
				}
				elems.mapSelectPointPopover.addClass('d-none');
				form.find('.post-story__form--barrios').empty().parent().addClass('d-none');

				callback(false);
			});

	}

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
		
		// App
		self.app = $('.app');

		// Navbar
		self.navbar = $('.navbar');
		self.navbarSearchInput = $('input.autocomplete');

		// Dashboard
		self.dashboard = $('.dashboard');
		self.noNeighborhood = $('.dashboard__no-barrio');
		self.noStories = $('.dashboard__no-stories');

		self.postStoryButtons = $('.post-story__button');
		self.postStoryForms = $('.post-story__form');
		self.postStoryCancelButton = $('.post-story__cancel-button');

		self.postNoticia = $('.post-story__form--noticia');
		self.postNoticiaBarrios = $('.post-story__form--noticia .post-story__form--barrios');
		self.postNoticiaSubmitButton = $('.post-story__form--noticia .post-story__submit-button');

		self.postEvento = $('.post-story__form--evento');
		self.postEventoSubmitButton = $('.post-story__form--evento .post-story__submit-button');

		self.postReporte = $('.post-story__form--reporte');
		self.postReporteSubmitButton = $('.post-story__form--reporte .post-story__submit-button');

		self.ubicacionActual = $('.dashboard__ubicacion');
		self.stories = $('.dashboard__stories');
		self.dashboardLoader = $('.dashboard__loader');

		// Inputs
		self.inputsWithMaxLength = $('input[maxlength], textarea[maxlength]');

		// Buttons (fire actions)
		self.buttonToggleDashboard = $('.button__toggle-dashboard');
		self.buttonFocusSearch = $('.button__focus-search');
		
		// Map
		self.map = $('.map');
		self.mapSelectPointPopover = $('.map__select-point');
		self.mapFilterButtons = $('.button--category');

		return self;

	};
	
	// Función que enlaza las funciones con los elementos a través de eventos
	var enlazarFunciones = function() {

		elems.buttonToggleDashboard.on('click', dashboardToggle);
		elems.buttonFocusSearch.on('click', focusSearchInput);
		elems.navbarSearchInput.on('focus', autocompleteOpen);

		elems.postStoryButtons.on('click', postStoryToggleForm);
		elems.postStoryCancelButton.on('click', postStoryCancel);

		elems.postNoticiaSubmitButton.on('click', postNoticiaSubmit);
		elems.postEventoSubmitButton.on('click', postEventoSubmit);
		elems.postReporteSubmitButton.on('click', postReporteSubmit);

		elems.inputsWithMaxLength.on({
			'focus keydown keyup': postRemainingChars,
			'blur': postHideRemainingChars
		});

		elems.mapFilterButtons.on('click', mapFilterMarkers);

    };

	var initApp = function() {

		// Asigna a elem los elementos
		elems = enlazarElems();
		enlazarFunciones();

		autocompleteInit();

		// Vuelve la sección 'Dashboard' a la última posición deseada por el usuario
		if (localStorage.getItem('nav') === 'expanded') {
			dashboardOpen();
		}
		
	}

	var initMap = function() {
		map = new google.maps.Map(document.getElementById('map'), {
			zoom: 13,
			center: new google.maps.LatLng(-38.7184, -62.2664),
			clickableIcons: false,
			gestureHandling: 'greedy',
			disableDefaultUI: true,
			minZoom: 12,
			maxZoom: 17,
			streetViewControl: false,
			mapTypeControl: false,
			styles: [
			  {
				"elementType": "geometry",
				"stylers": [
				  {
					"color": "#f5f5f5"
				  }
				]
			  },
			  {
				"elementType": "labels.icon",
				"stylers": [
				  {
					"visibility": "off"
				  }
				]
			  },
			  {
				"elementType": "labels.text.fill",
				"stylers": [
				  {
					"color": "#616161"
				  }
				]
			  },
			  {
				"elementType": "labels.text.stroke",
				"stylers": [
				  {
					"color": "#f5f5f5"
				  }
				]
			  },
			  {
				"featureType": "administrative.land_parcel",
				"elementType": "labels.text.fill",
				"stylers": [
				  {
					"color": "#bdbdbd"
				  }
				]
			  },
			  {
				"featureType": "poi",
				"elementType": "geometry",
				"stylers": [
				  {
					"color": "#eeeeee"
				  }
				]
			  },
			  {
				"featureType": "poi",
				"elementType": "labels.text.fill",
				"stylers": [
				  {
					"color": "#757575"
				  }
				]
			  },
			  {
				"featureType": "poi.park",
				"elementType": "geometry",
				"stylers": [
				  {
					"color": "#e5e5e5"
				  }
				]
			  },
			  {
				"featureType": "poi.park",
				"elementType": "labels.text.fill",
				"stylers": [
				  {
					"color": "#9e9e9e"
				  }
				]
			  },
			  {
				"featureType": "road",
				"elementType": "geometry",
				"stylers": [
				  {
					"color": "#ffffff"
				  }
				]
			  },
			  {
				"featureType": "road.arterial",
				"elementType": "labels.text.fill",
				"stylers": [
				  {
					"color": "#757575"
				  }
				]
			  },
			  {
				"featureType": "road.highway",
				"elementType": "geometry",
				"stylers": [
				  {
					"color": "#dadada"
				  }
				]
			  },
			  {
				"featureType": "road.highway",
				"elementType": "labels.text.fill",
				"stylers": [
				  {
					"color": "#616161"
				  }
				]
			  },
			  {
				"featureType": "road.local",
				"elementType": "labels.text.fill",
				"stylers": [
				  {
					"color": "#9e9e9e"
				  }
				]
			  },
			  {
				"featureType": "transit.line",
				"elementType": "geometry",
				"stylers": [
				  {
					"color": "#e5e5e5"
				  }
				]
			  },
			  {
				"featureType": "transit.station",
				"elementType": "geometry",
				"stylers": [
				  {
					"color": "#eeeeee"
				  }
				]
			  },
			  {
				"featureType": "water",
				"elementType": "geometry",
				"stylers": [
				  {
					"color": "#c9c9c9"
				  }
				]
			  },
			  {
				"featureType": "water",
				"elementType": "labels.text.fill",
				"stylers": [
				  {
					"color": "#9e9e9e"
				  }
				]
			  }
			]
		});

		mapLoadMarkers();

		google.maps.event.addListener(map, 'click', function() {
			infoWindow.close();
		})
	
	}

	return {
		initApp: initApp,
		initMap: initMap
	};


})();

$(document).ready(App.initApp);

function initMap() {
	App.initMap();
}