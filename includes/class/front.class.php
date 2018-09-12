<?php
namespace osm_poi;
class front{

	public function generate_map($params){

		$params["address"] = "314 chemin de la charlisse, Vaugneray";

		$mapquest_key = get_option("osm_poi_mapquest_key" , false);
		if($mapquest_key === false) return "Please fill the MapQuest API Key in the settings admin page";


		if(empty($params["address"])) 		return "Please provide an adress to the map";

		// Paramètres par défaut
		if(empty($params["height"])) 		$params["height"] = "650";
		if(empty($params["zoom"])) 			$params["zoom"] = "15";
		if(empty($params["zoom-mobile"])) 	$params["zoom-mobile"] = "12";
		if(empty($params["icon"])){
			$attachment_id 	= get_option( 'osm_poi_attachement_id', "" );
			$img_src 		= wp_get_attachment_url($attachment_id);
        	if($img_src === false) $img_src = OSM_POI_URL."/images/marker.png";

			$params["icon"] = $img_src;
		}

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
		

		// Variable de retour
		$retour = '';


		$retour .= "<script>window.onload = function(){
					    osm_poi_init_map('".$lat."' , '".$lng."' , '".$params["icon"]."'); 
					}
					</script>";


		// Affichage de la map
		$retour .= '<div id="map"></div>';

		// Gestions des POIS
		$retour .= "<div id='H2I_ANN_MAN_page_annonce_map_legende'>";


		$pois = array(
		  'grocery_or_supermarket'  => 'Supermarchés',
		  'restaurant'              => 'Restaurants',
		  'bakery'                  => 'Boulangeries',
		  'hospital'                => 'Hopitaux',
		  'school'                  => 'Ecoles',
		  'doctor'                  => 'Docteurs',
		  'parking'                 => 'Parkings'
		);

		if(get_option("h2i_ann_man_has_metro") == 1) 	$pois['subway_station'] = 'Métros';
		if(get_option("h2i_ann_man_has_tramway") == 1) 	$pois['light_rail_station'] = 'Tramway';

		$retour .= '<div id="title-poi_container">
						<a class="osm-title-poi " data-toggle="collapse" data-target="#osm-checkboxes-poi" ><i class="fa fa-map-marker" aria-hidden="true"></i> Afficher les points d\'intérets à proximité</a>
					</div>';
		$retour .= '<div id="checkboxes-poi-container">
						<div id="osm-checkboxes-poi" class="collapse">';


		foreach($pois as $category_name => $libelle){

			$retour .= '	<div class="osm-checkbox-poi">
								<input id="'.$category_name.'" type="checkbox" name="'.$category_name.'" onclick=\'osm_poi_show_poi_nearby("'.$category_name.'")\'>
								<label for="'.$category_name.'"><img src="'.OSM_POI_URL.'/images/pois/marker-'.$category_name.'.png" /> '.$libelle.'</label>
							</div>';

		}

		$retour .= '	<div style="clear:both"></div>
						</div>
					</div>
					';
		

		$retour .= "</div>";




		// Hauteur de la map
		$retour .= '
		<style type="text/css">
			#map{ height:'.$params["height"].'px !important;	}
		</style>		
		';


		return $retour;



	}


}