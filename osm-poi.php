<?php
/*
Plugin Name: Open Street Map - POI manager
Plugin URI: http://www.h2i.fr
Description: Permet d'afficher sur une map OSM, un marqueur avec des points d'intérets
Version: 1.1
Author: Baptiste Lozano
Contributors: Baptiste Lozano
Author URI: http://www.h2i.fr
Copyright 2018 H2I

Note : https://github.com/leaflet-extras/leaflet-providers utilisé pour le style de la map

*/

// ===================== Constantes générales du plugin =====================
define( 'OSM_POI_URL'		, plugins_url('/', __FILE__) );
define( 'OSM_POI_DIR'		, dirname(__FILE__) );

define( 'OSM_POI_LIST' , array(
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
));

// ===================== Autoloader des classes du plugin avec namespace  =====================
function osm_poi_autoloader( $class_name ) {

	if ( false !== strpos( $class_name, 'osm_poi\\' ) ) {

		$class_name = str_replace("osm_poi\\", "", $class_name);
		$classFile 	= OSM_POI_DIR . '/includes/class/' .$class_name . '.class.php';
	
        if (file_exists($classFile)) require_once $classFile;
        else{
        	echo ("Missing class : ".$classFile);
        	die();
        }
	   
	}

}
spl_autoload_register( 'osm_poi_autoloader' );


// ===================== Initialisation du back office =====================
function osm_poi_init(){

	require_once( OSM_POI_DIR . "/includes/functions.php"  );

	// Settings
	global $OSM_POI_Back;
	$OSM_POI_Back 	= new osm_poi\back();


}
add_action( 'init', 'osm_poi_init' );

// ===================== Création du shortcode =====================
function sc_osm_poi_get_map($atts){

	// ex : [osm_poi_map lat="45.75" lng="4.85" height="300" zoom="16" zoom-mobile="18"]

	shortcode_atts( array(
		'height'      => '',
		'zoom'        => '',
		'zoom-mobile' => '',
		'icon'        => '',
		'disable_zoom' => '',

	), $atts , 'sc_gaddr_get_map');

	if(!is_array($atts)) $atts = array();
	
	// Le shortcode affiche les pois directement
	$atts["display_pois"] = 1;

	$OSM_POI_Front 	= new osm_poi\front();
	return $OSM_POI_Front->generate_map($atts);

}
add_shortcode('osm_poi_map', 'sc_osm_poi_get_map');


// ===================== Fonction appelable depuis un thème par exemple =====================
function osm_poi_get_map($params = array()){

	if(!is_array($params)) $params = array();
	
	// La fonction affiche les pois séparément
	$params["display_pois"] = 0;

	$OSM_POI_Front 	= new osm_poi\front();
	return $OSM_POI_Front->generate_map($params);


}


function test_osm(){

	$params = array("lat" => "45.75" , "lng" => "4.85"  , "height" => "300" , "zoom" => "16" ,  "zoom-mobile" => "18");

	$retour = osm_poi_get_map($params);

}
//add_action("wp_footer" , "test_osm");

// ===================== Ajout des url & patch JS dans le head de la page =====================
function osm_poi_add_js_var_in_head(){

	echo "\n"."<script type='text/javascript'>var path_to_plugin_osm_poi = '".OSM_POI_URL."';</script>";



}
add_action('wp_head', 'osm_poi_add_js_var_in_head');


// ===================== Création du shortcode =====================
function osm_poi_parse_query($atts){

	if(empty($_GET["osm_poi_action"]) || $_GET["osm_poi_action"] <> "get_places") return;

	$lat = (!empty($_GET["lat"])) ? urldecode($_GET["lat"]) : '';
	if($lat == '') return;

	$lng = (!empty($_GET["lng"])) ? urldecode($_GET["lng"]) : '';
	if($lng == '') return;

	$type_poi = (!empty($_GET["type_poi"])) ? urldecode($_GET["type_poi"]) : '';
	if($type_poi == '') return;


	$option_name 	= sanitize_title($lat."-".$lng."-".$type_poi);
	$places_json	= get_option($option_name , false);
	if($places_json === false){

		// Appel de l'API distante
		$url            = "http://srvweb.h2i.fr/wordpress-referentiel/scripts/google-places.php?lat=".$lat."&lng=".$lng."&type_poi=".$type_poi."&google_key=".get_option("osm_poi_google_key");
		$remote_request = wp_remote_request($url);
		$places_json    = wp_remote_retrieve_body($remote_request);
		update_option($option_name , $places_json);

	}


	// Formattage des résultats
	$retour = array();
	$places_json = json_decode($places_json);
	foreach($places_json->results as $result){

		if(sizeof($retour) == 10) continue;

		$retour[] = array("name" => $result->name , "vicinity" => $result->vicinity , "lat" => $result->geometry->location->lat , "lng" => $result->geometry->location->lng);

	}



	header('Content-Type: application/json');
	echo json_encode($retour);
	die();
	


}
add_action('parse_query', 'osm_poi_parse_query');


// ===================== Gestion des mises à jour du plugin =====================
require OSM_POI_DIR.'/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'http://srvweb.h2i.fr/wordpress-referentiel/osm-poi.json',
    __FILE__
);



function osm_poi_enqueue_scripts() {

    wp_enqueue_script('leaflet-js', '//unpkg.com/leaflet@1.3.1/dist/leaflet.js"', array("jquery"), '1.3.1', true);
    wp_enqueue_script('leaflet-provider-js', OSM_POI_URL.'/js/leaflet-providers.js', array("jquery"), '1.3.1', true);

	wp_enqueue_script('osm_poi_js', OSM_POI_URL."js/front.js", array( 'jquery' ), '1.0', false );
	wp_enqueue_style('osm_poi_css', OSM_POI_URL."css/front.css");
	wp_enqueue_style('leaflet-style', '//unpkg.com/leaflet@1.3.1/dist/leaflet.css');
	
}
add_action( 'wp_enqueue_scripts', 'osm_poi_enqueue_scripts' );



