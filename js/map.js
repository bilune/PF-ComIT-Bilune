function initMap() {

	window.map = new google.maps.Map(document.getElementById('map'), {
	  zoom: 13,
	  center: new google.maps.LatLng(-38.7184, -62.2664),
	  clickableIcons: false,
	  gestureHandling: 'greedy',
	//   maxZoom: 16,
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

	window.markers = {};
	window.infoWindow = null;

	$.getJSON('http://localhost/api/markers.json', {}, function(response, status, xhr) {
		if (status !== 'error') {

			infoWindow = new google.maps.InfoWindow({
				content: '<img src="icons/loader.gif" width="30" height="30" class="mx-auto my-5">'
			});


			response.data.forEach(function(marker) {
				markers[marker.id] = {
					marker: new google.maps.Marker({
						position: marker.geometry,
						map: map,
						icon: 'http://localhost/xaca/icons/spotlight-poi-'+marker.category+'.png'
					}),
					categoria: marker.category
				}

				markers[marker.id].marker.addListener('click', function() {

					if ($('.app').hasClass('expanded')) { // Está expandido -> se enfoca la historia seleccionada
						var $card = $('#'+marker.id);
						var scrollTop = $card.length ? $card.position().top : 0;

						$('.dashboard')
						.animate({
							scrollTop: '+=' + scrollTop
						}, 1000);

					} else { // Está contraído -> se abre una InfoWindow

						infoWindow.open(map, markers[marker.id].marker);

						$.getJSON('http://localhost/api/historia.json', {}, function(response, status) {
							if (status !== 'error') {
								infoWindow.setContent(response.data[0].html);
							}
						});
					}

				});
			});
		}
	});

	google.maps.event.addListener(map, 'click', function() {
		infoWindow.close();
	})

	new google.maps.Marker
}