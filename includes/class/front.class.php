<?php
namespace osm_poi;
class front{

	public function generate_map($params){

		// Vérifie qu'on à donné un endroit ou poser le marqueur
		if(empty($params["address"]) && empty($params["lat"]) && empty($params["lng"]))
			return "Please provide an address, or a couple of lat/lng to the map";


		// Paramètres par défaut
		if(empty($params["height"])) 		$params["height"] = "650";
		if(empty($params["zoom"])) 			$params["zoom"] = "15";
		if(empty($params["zoom-mobile"])) 	$params["zoom-mobile"] = "17";
		if(empty($params["icon"])){
			$attachment_id 	= get_option( 'osm_poi_attachement_id', "" );
			$img_src 		= wp_get_attachment_url($attachment_id);
        	if($img_src === false) $img_src = OSM_POI_URL."/images/marker.png";

			$params["icon"] = $img_src;
		}


		// Zoom (mobile ou non)
		$zoom = (wp_is_mobile()) ? $params["zoom-mobile"] : $params["zoom"];


		// Si on à passé une adresse à géocoder
		if(!empty($params["address"])){

			// Vérifie qu'on à une clé d'API valide
			$mapquest_key = get_option("osm_poi_mapquest_key" , false);
			if($mapquest_key === false) return "Please fill the MapQuest API Key in the settings admin page";

			// Option de l'adresse
			$adresse_slug = sanitize_title($params["address"]);
			$adresse_geocoding_option = "osm-geocode-".$adresse_slug;
			

			// Geocoding de l'adresse
			$geocoding_result = get_option($adresse_geocoding_option , false);
			if($geocoding_result === false){

				$remote_request = wp_remote_request("http://open.mapquestapi.com/geocoding/v1/address?key=".$mapquest_key."&location=".$params["address"]);
				$geocoding_result = wp_remote_retrieve_body($remote_request);


				update_option($adresse_geocoding_option , $geocoding_result);

			}

			$geocoding_result = json_decode($geocoding_result);
			$lat_lng = $geocoding_result->results[0]->locations[0]->displayLatLng;
			$lat = $lat_lng->lat;
			$lng = $lat_lng->lng;

		}
		else{
			$lat = $params["lat"];
			$lng = $params["lng"];
		}
		

		// Variable de retour
		$map = '';

		$map .= "<script>window.onload = function(){
					    osm_poi_init_map('".$lat."' , '".$lng."' , '".$params["icon"]."' , '".$zoom."'); 
					}
					</script>";


		// Affichage de la map
		$map .= '<div id="map"></div>';


		// Hauteur de la map
		$map .= '
		<style type="text/css">
			#map{ height:'.$params["height"].'px !important;	}
		</style>		
		';



		// Gestions des POIS
		$legende_pois = "";
		$legende_pois .= "<div id='H2I_ANN_MAN_page_annonce_map_legende'>";


		$pois = array(
		  'grocery_or_supermarket'  => 'Supermarchés',
		  'restaurant'              => 'Restaurants',
		  'bakery'                  => 'Boulangeries',
		  'hospital'                => 'Hopitaux',
		  'school'                  => 'Ecoles',
		  'doctor'                  => 'Docteurs',
		  'parking'                 => 'Parkings',
		  'subway_station'			=> 'Métros',
		  'light_rail_station'		=> 'Tramway',
		  'gym'						=> 'Salle de sport',
		  'florist'					=> 'Fleuriste',
		  'pharmacy'				=> 'Pharmacie',
		  'movie_theater'			=> 'Cinéma',
		  'bus_station'				=> 'Bus'
		);

	

		$legende_pois .= '<div id="title-poi_container">
						<a class="osm-title-poi " data-toggle="collapse" data-target="#osm-checkboxes-poi" ><i class="fa fa-map-marker" aria-hidden="true"></i> Afficher les points d\'intérets à proximité</a>
					</div>';
		$legende_pois .= '<div id="checkboxes-poi-container">
						<div id="osm-checkboxes-poi" class="collapse">';


		foreach($pois as $category_name => $libelle){

			$legende_pois .= '	<div class="osm-checkbox-poi" id="osm-checkbox-poi-'.$category_name.'">
								<input id="'.$category_name.'" type="checkbox" name="'.$category_name.'" onclick=\'osm_poi_show_poi_nearby("'.$category_name.'")\'>
								<label for="'.$category_name.'"><img src="'.OSM_POI_URL.'/images/pois/marker-'.$category_name.'.png" /> '.$libelle.'</label>
							</div>';

		}

		$legende_pois .= '	<div style="clear:both"></div>
						</div>
					</div>
					';
		

		$legende_pois .= "</div>";

	


		// Si on passe par un shortcode
		if($params["display_pois"] == 1){
			return $map . $legende_pois;
		}
		else{
			return array("map" => $map , "legende_pois" => $legende_pois);
		}


	}


}