<?php
/*
Plugin Name: Google Maps Shortcode
Plugin URI: http://www.globalis-ms.com
Description: 
Version: 1.0.0
Author: Georges-Antoine RICHARD, Globalis-MediaSystem
Author URI: http://www.globalis-ms.com
License: GPL2
*/

add_shortcode( 'gmap', 'gmaps_shortcode' );

function gmaps_shortcode($atts)
{
	extract( shortcode_atts( array(
	'div_id'			=> 'gmaps'
	'address' 			=> '',
	'zipcode' 			=> '',
	'city'	  			=> '',
	'country' 			=> '',
	'marker_title' 		=> '',
	'marker_content' 	=> '',
	'marker_tooltip'	=> '',
	'zoom_level'		=> '15',
	), $atts ) );

	$val = geocode_address($address.' '.$zipcode.' '.$city.' '.$country);

	$output = 	'<div id="'.$div_id.'"></div>';

	if($val)
	{
		$output = 	'   <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3.exp&amp;sensor=false"></script>
						<script type="text/javascript">

						function initialize()
						{
							var myLatlng = new google.maps.LatLng('.$val["lat"].' ,'.$val["lng"].');
							var contentString = "<div><b>'. $marker_title .'</b><p>'. $marker_content .'</p></div>";
		  					var mapOptions = 	{
		    										zoom: '.$zoom_level.',
		    										center: myLatlng,
		    										mapTypeId: google.maps.MapTypeId.ROADMAP
		  										};
		  					map = new google.maps.Map(document.getElementById("'. $div_id .'"), mapOptions);

		  					var infowindow = new google.maps.InfoWindow({ content: contentString });

		  					var marker = new google.maps.Marker({ position: myLatlng, map: map, title: "'. $marker_tooltip .'"});

		  					google.maps.event.addListener(marker, "click", function() {
		    					infowindow.open(map,marker);
		  					});
						}
							
							google.maps.event.addDomListener(window, "load", initialize);	

					</script>					
					';
	}
	
	return $output;
}

function geocode_address($address)
{
	$response = array();
	$response['lat'] = '';
	$response['lng'] = '';

	$encoded_address = str_replace(' ', '+', 'http://maps.googleapis.com/maps/api/geocode/json?address=' . $address);

	// To assure compatibility accross the servers we prefer use wp_remote_get() instead of file_get_contents()

	//$json_response = file_get_contents($encoded_address . '&sensor=false', 0, null, null);
	$json_response = wp_remote_get($encoded_address . '&sensor=false');

	$decode_response = json_decode($json_response['body']);

	switch($decode_response->status)
	{
		case 'OK' :
					$response['lat'] = $decode_response->results[0]->geometry->location->lat;
					$response['lng'] = $decode_response->results[0]->geometry->location->lng;
		break;

		case 'ZERO_RESULTS' :

		case 'OVER_QUERY_LIMIT' :

		case 'REQUEST_DENIED' :

		case 'INVALID_REQUEST' :

		case 'UNKNOWN_ERROR' :

			return null;

		default:
	}

	return $response;
}