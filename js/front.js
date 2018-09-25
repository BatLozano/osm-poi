var osm_poi_map = null;
var osm_poi_markers = []; 

var osm_poi_main_lat;
var osm_poi_main_lng;

var osm_markers_per_type_place = [];
var osm_all_category_asked = [];

var is_zoom_disabled = 0;

// Initialisation de la map
function osm_poi_init_map(lat , lon , icon , zoom, map_style , disable_zoom) {

    osm_poi_main_lat = lat;
    osm_poi_main_lng = lon;

    osm_poi_map = new L.Map('map');
    osm_poi_map.setView([osm_poi_main_lat, osm_poi_main_lng], zoom);


    is_zoom_disabled = disable_zoom;
    if(disable_zoom == 1){
      osm_poi_map.touchZoom.disable();
      osm_poi_map.doubleClickZoom.disable();
      osm_poi_map.scrollWheelZoom.disable();
      osm_poi_map.boxZoom.disable();
      osm_poi_map.keyboard.disable();
      jQuery(".leaflet-control-zoom").css("visibility", "hidden");
    }

    osm_poi_map.scrollWheelZoom.disable();

    L.tileLayer.provider(map_style, {
        app_id: "VzgTyDqdfILl99Vb5T70",
        app_code: "FXZ78YtYUmOErRDIB_MTeQ"
    }).addTo(osm_poi_map);          

    var myIcon = L.icon({
        iconUrl: icon,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -35],
    });


    var marker = L.marker([osm_poi_main_lat, osm_poi_main_lng] , { icon: myIcon }).addTo(osm_poi_map);

    //marker.bindPopup();

    osm_poi_markers.push(marker); 
   

}

// Modification des bounds de la map pour voir tous les markers
function osm_poi_fitbounds() {
    
    if(is_zoom_disabled == 0){
      var group = new L.featureGroup(osm_poi_markers); 
      osm_poi_map.fitBounds(group.getBounds()); 
    }
    
}





// Gestion des pois
function osm_poi_show_poi_nearby(poi_type){

  // Initialisation du tableau qui contiendra tous les markers
  category_asked = poi_type;
  if (typeof osm_markers_per_type_place[category_asked] == "undefined") osm_markers_per_type_place[category_asked] = [];

  var checked = document.getElementById(poi_type).checked;

  // On affiche les markers de cette categorie
  if(checked){

    // Ajout de la categorie à la liste des categories selectionnées
    osm_all_category_asked.push(poi_type);

    var nb_markers_added = 0;

    // Définition du marqueur
    var myIcon = L.icon({
        iconUrl: osm_poi_markers_images[poi_type],
        iconSize: [25, 40],
        iconAnchor: [12, 40],
        popupAnchor: [0, -35],
    });


    jQuery.ajax({

        type    : 'GET',
        crossDomain: true,
        dataType: "json",
        url     : "/?osm_poi_action=get_places&lat="+osm_poi_main_lat+"&lng="+osm_poi_main_lng+"&type_poi="+poi_type,
        success: function(results){

            jQuery.each( results, function( key, poi ) {

                var marker = L.marker([poi.lat, poi.lng] , { icon: myIcon }).addTo(osm_poi_map);
                marker.bindPopup("<strong>"+poi.name+"</strong><br/>"+poi.vicinity);
                
                osm_poi_markers.push(marker); 
                osm_markers_per_type_place[category_asked].push(marker);

            });

            nb_markers_added = results.length;

        },
        complete: function (data) {
          if(nb_markers_added > 0) osm_poi_fitbounds();
        }

    });



  }

  // On décoche les markers de cette categorie
  else{

    for(i=0;i<osm_markers_per_type_place[category_asked].length;i++) {
      osm_poi_map.removeLayer(osm_markers_per_type_place[category_asked][i]);
    }  

    osm_markers_per_type_place[category_asked] = [];

  }



}