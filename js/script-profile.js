var App = (function() {

	// Objeto que contiene todos los controles de jQuery
	var elems = {};
	
	var markersFilter = {
		noticias: true,
		eventos: true,
		reportes: true
	};

	var loadingMoreStories = false;

	var map = null;
	var markers = {};
	var infoWindow = null;
	var mapBounds = null;

	var lastStoryDateTime = null;

	

	// --------DASHBOARD--------

	// Pide al servidor y carga las historias correspondientes al barrio seleccionado
	var dashboardLoadStories = function(callback) {

		var dashboard = elems.dashboard;

		var query = {
			query: 'historias',
			usuario: getUrlParameter('username')
		};

		if (lastStoryDateTime) {
			query.antes_de = lastStoryDateTime;
		}

		$.getJSON('http://localhost/xaca/server/api/historia.php', query, function(result, status) {
			if (status === 'error') {
				// TO DO: Handle error
			} else {

				if (callback) callback(result.data.length === 0);

				result.data.forEach(function(historia) {

					lastStoryDateTime = historia.fecha_creacion;
					
					var card = elems.dashboardStories
						.append(historia.html)
						.find('.card:last');

					// Acorta los textos de las historias y agrega un botón para ver más
					shortenDescriptions(card);

					// Eventos para resaltar marcador cuando se hace 'hover' sobre una historia
					var historiaID = 'id' + historia.id;
					card.on({
							'mouseenter': function() {
								markers[historiaID].marker.setAnimation(google.maps.Animation.BOUNCE);
							},
							'mouseleave': function() {
								markers[historiaID].marker.setAnimation(null);
							}
						});
				});

				mapSetMarkers(result.data);
			}
		});
	}

	var dashboardScrollBottom = function() {
		var dashboard = elems.dashboard;

		if (!loadingMoreStories && dashboard.scrollTop() + dashboard.outerHeight() === dashboard.prop('scrollHeight')) {
			elems.dashboardLoader.removeClass('d-none').addClass('d-block');
			loadingMoreStories = true;

			dashboardLoadStories(function(noMoreStories) {
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

	var mapSetMarkers = function(arrayOfMarkers) {

		arrayOfMarkers.forEach(function(marker) {

			var markerID = 'id' + marker.id;

			if (markerID in markers) {
				return false;
			}

			mapBounds.extend({
				lat: marker.geometry.lat,
				lng: marker.geometry.lng
			});


			markers[markerID] = {
				marker: new google.maps.Marker({
					position: marker.geometry,
					map: map,
					animation: google.maps.Animation.DROP,
					icon: 'http://localhost/xaca/icons/spotlight-poi-'+marker.category+'.png'
				}),
				categoria: marker.category
			}

			markers[markerID].marker.addListener('click', function() {
				
				if ($(window).width() > 992) { // Está expandido -> se enfoca la historia seleccionada
					var $card = $('#'+markerID);
					var scrollTop = $card.length ? $card.position().top : 0;

					$('.dashboard')
					.animate({
						scrollTop: '+=' + scrollTop
					}, 1000);

				} else { // Está contraído -> se abre una InfoWindow

					infoWindow.open(map, markers[markerID].marker);

					$.getJSON('http://localhost/api/historia.json', {}, function(response, status) {
						if (status !== 'error') {
							infoWindow.setContent(response.data[0].html);
						}
					});
				}

			});
		});

		map.fitBounds(mapBounds);

	}

	// --------OTHERS--------

	var getUrlParameter = function getUrlParameter(sParam) {
		var sPageURL = decodeURIComponent(window.location.search.substring(1)),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;
	
		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');
	
			if (sParameterName[0] === sParam) {
				return sParameterName[1] === undefined ? true : sParameterName[1];
			}
		}
	}



	// --------GENERAL--------

	// Función que selecciona todos los elementos necesarios y los retorna en un objeto
	var enlazarElems = function () {
		var self = {};
		
		// Dashboard
		self.dashboard = $('.dashboard');
		self.dashboardLoader = $('.dashboard .loader');
		self.dashboardStories = $('.dashboard__stories');

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

		dashboardLoadStories();
		
	}

	var initMap = function() {
		map = new google.maps.Map(document.getElementById('map'), {
			zoom: 13,
			center: new google.maps.LatLng(-38.7184, -62.2664),
			clickableIcons: false,
			disableDefaultUI: true,
		    maxZoom: 16,
			minZoom: 12,
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

		google.maps.event.addListener(map, 'click', function() {
			infoWindow.close();
		});

		infoWindow = new google.maps.InfoWindow({
			content: '<img src="icons/loader.gif" width="30" height="30" class="mx-auto my-5">'
		});

		mapBounds = new google.maps.LatLngBounds();

	}

	return {
		init: init,
		initMap : initMap
	};


})();


function initMap() {
	$(document).ready(App.init);
	$(document).ready(App.initMap);
}