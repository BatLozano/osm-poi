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
			$attachment_id 	= get_option( 'osm_poi_attachement_main', "" );
			$img_src 		= wp_get_attachment_url($attachment_id);
        	if($img_src === false) $img_src = OSM_POI_URL."/images/marker.png";

			$params["icon"] = $img_src;
		}
		$params["disable_zoom"] = (isset($params["disable_zoom"])) ? intval($params["disable_zoom"]) : "0";



		// Zoom (mobile ou non)
		$params["zoom"] = (wp_is_mobile()) ? $params["zoom-mobile"] : $params["zoom"];

		// Style
		$params["style"] = get_option("osm_poi_map_style");

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

				$remote_request 	= wp_remote_request("http://open.mapquestapi.com/geocoding/v1/address?key=".$mapquest_key."&location=".$params["address"]);
				$geocoding_result 	= wp_remote_retrieve_body($remote_request);

				update_option($adresse_geocoding_option , $geocoding_result);

			}

			$geocoding_result = json_decode($geocoding_result);
			$lat_lng = $geocoding_result->results[0]->locations[0]->displayLatLng;
			$params["lat"] = $lat_lng->lat;
			$params["lng"] = $lat_lng->lng;

		}

		// Variable de retour
		$map = '';

		// Calcul de la taille du marqueur de base
		$ex 	= explode("/wp-content/" , $params["icon"]);
		$main_marker_path = WP_CONTENT_DIR."/".$ex[1];
		list($w, $h) = getimagesize($main_marker_path);
		$map .= "<script>
				var main_marker_path_height = ".$h.";
				var main_marker_path_width = ".$w.";
				</script>";
		

		// Initialisation de la map
		$map .= "<script>
				window.onload = function(){
					osm_poi_init_map('".json_encode($params)."'); 
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


		// Calcul des images de chaque poi
		$imgs_pois = array();

		$map .= "<script>";
		$map .= "var osm_poi_markers_images = [];\n";
		$map .= "var osm_poi_markers_size = [];\n";

		foreach(OSM_POI_LIST as $category_name => $libelle){
			
			$attachment_id 	= get_option( 'osm_poi_attachement_'.$category_name, "" );
			$img_url 		= wp_get_attachment_url($attachment_id);
			
			if($img_url === false){
				$img 	 = "images/pois/marker-".$category_name.".png";
				$img_url = OSM_POI_URL.$img;
				$img_path = OSM_POI_DIR."/".$img;
			}
			else{
				$ex 	= explode("/wp-content/" , $img_url);
				$img 	= $ex[1];
				$img_path = WP_CONTENT_DIR."/".$img;
			}


			if(file_exists($img_path)){

				$imgs_pois[$category_name] = $img_url;
			
				// Url de l'image du marker
				$map .= "osm_poi_markers_images['".$category_name."'] = '".$img_url."';\n";
				
				// Taille de l'image du marqueur
				list($w, $h) = getimagesize($img_path);
				$map .= "osm_poi_markers_size['".$category_name."'] = {'h' : '".$h."' , 'w' : '".$h."'};\n";

			}

		}
		$map .= "</script>";
	

		// Gestions des POIS
		$legende_pois = "";
		$legende_pois .= "<div id='H2I_ANN_MAN_page_annonce_map_legende'>";


		$legende_pois .= '<div id="title-poi_container">
						<a class="osm-title-poi " data-toggle="collapse" data-target="#osm-checkboxes-poi" ><i class="fa fa-map-marker" aria-hidden="true"></i> Afficher les points d\'intérets à proximité</a>
					</div>';
		$legende_pois .= '<div id="checkboxes-poi-container">
						<div id="osm-checkboxes-poi" class="collapse">';


		$libelle_pois = OSM_POI_LIST;
		foreach($imgs_pois as $category_name => $img_url){
			$legende_pois .= '<div class="osm-checkbox-poi"><span onclick=\'osm_poi_show_poi_nearby("'.$category_name.'")\'><img src="'.$imgs_pois[$category_name].'" /> '.$libelle_pois[$category_name].'</span></div>';
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