<?php
/*
Plugin Name: Google Maps Shortcode
Plugin URI: http://www.globalis-ms.com
Description: 
Version: 1.0.0
Author: Georges-Antoine RICHARD, Globalis-ms
Author URI: http://www.globalis-ms.com
License: GPL2
*/

if(!class_exists('gmapsc'))
{
	class gmapsc
	{
		public function __construct()
		{
			add_shortcode( 'gmap', array($this,'gmaps_shortcode'));
		}

		public function gmaps_shortcode($atts)
		{
			extract( shortcode_atts( array(
			'div_id'			=> 'gmaps',
			'width'				=> '540',
			'height'			=> '280',
			'address' 			=> '',
			'zipcode' 			=> '',
			'city'	  			=> '',
			'country' 			=> '',
			'marker_title' 		=> 'More information',
			'marker_content' 	=> '',
			'marker_tooltip'	=> '',
			'zoom_level'		=> '15',
			'map_type'			=> 'ROADMAP',
			), $atts ) );

			$val = $this->geocode_address($address.' '.$zipcode.' '.$city.' '.$country);

			$output = '<div id="'.$div_id.'" style="width:'. $width .'px;height:'. $height .'px;"></div>';

			if($val)
			{	
				$map_type_array = array('ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN');

				$map_type = in_array($map_type, $map_type_array) ? $map_type : $map_type_array[0];
				$marker_content = !empty($marker_content) ? $marker_content : $address.' '.$zipcode.' '.$city;

				$output.= 	'<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3.exp&amp;sensor=false"></script>
							<script type="text/javascript">
								function initialize()
								{
									var myLatlng = new google.maps.LatLng('.$val["lat"].' ,'.$val["lng"].');
									var contentString = "<div><b>'. $marker_title .'</b><p>'. $marker_content .'</p></div>";
				  					var mapOptions = 	{
				    										zoom: '.$zoom_level.',
				    										center: myLatlng,
				    										mapTypeId: google.maps.MapTypeId.'. $map_type .'
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

		private function geocode_address($address)
		{
			$address_clean = str_replace(' ', '+',  trim($address));

			if ( false === ( $response = get_transient( 'gmaps_'.$address_clean ) ) )
			{	
				$response = array();
				$response['lat'] = '';
				$response['lng'] = '';

				$encoded_address = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . $address_clean;

				// To assure compatibility accross the servers we prefer use wp_remote_get() instead of file_get_contents()

				//$json_response = file_get_contents($encoded_address . '&sensor=false', 0, null, null);
				$json_response = wp_remote_get($encoded_address . '&sensor=false');

				if ( is_wp_error( $json_response ) )
				{
				   $error_message = $json_response->get_error_message();
				   //TODO : log error 
				   return null;
				}
				else
				{
					$decode_response = json_decode($json_response['body']);

					switch($decode_response->status)
					{
						case 'OK' :

									$response['lat'] = $decode_response->results[0]->geometry->location->lat;
									$response['lng'] = $decode_response->results[0]->geometry->location->lng;

									set_transient( 'gmaps_'.$address_clean, $response, 4 * WEEK_IN_SECONDS );

						break;

						case 'ZERO_RESULTS' :

						case 'OVER_QUERY_LIMIT' :

						case 'REQUEST_DENIED' :

						case 'INVALID_REQUEST' :

						case 'UNKNOWN_ERROR' :

									return null;
									
					}
				}
			}

			return $response;
		}
	}

	$my_gmapsc = new gmapsc();
}





