<?php
namespace osm_poi;
class back{


	public function __construct()
	{

		// Ajout d'un menu administrateur pour le plugin
		add_action('admin_menu', array($this, 'init'));
		add_action('admin_footer', 	array($this, 	'print_scripts'));

	}

		// ============================== Gestion de l'admin ==============================
	public function init(){

		 add_options_page(
	        'OSM POI configuration',
	        'OSM POI',
	        'edit_pages',
	        'osm-poi-config',
	        array($this , 'main')
	    );


	}

	public function main(){

		$retourHtml = '';

        $retourHtml .= "<h1>Open Street Map POI configuration</h1>";

        /* Exemplde code de restriction de plugin
        if (!is_plugin_active('Entities.Construction/Entities.Construction.php')) {
            $error_plugin[] = "Plugin Entities Construction non installé";
        }
        */

        global $wpdb , $table_prefix;

        // Gestion de la sauvegarde des données
        if (!empty($_POST)) {
            $erreurs = $this->save_configuration($_POST);

            if ($erreurs == "") {
                $retourHtml .= '<div class="updated fade"> <p><em>Setting saved !</em></p>  </div>';
            } else {
                $retourHtml .= '<div class="error fade"><p><em>'.$erreurs.'</em></p></div>';
            }
        }

        /* =================== Début du formulaire =================== */
        $retourHtml .= '<div class="wrap">';

        $retourHtml .= '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '">';

   
        /* =================== Logins =================== */
        $retourHtml .= '<table class="form-table">';
        $retourHtml .= '<tbody>';

        $retourHtml .= '<tr><th scope="row"><label>MapQuest API Key</label></th>';
        $retourHtml .= '<td><input name="osm_poi_mapquest_key" type="text" id="osm_poi_mapquest_key" class="regular-text" value="'.get_option("osm_poi_mapquest_key").'" /></td></tr>';


        $retourHtml .= '<tr><th scope="row"><label>Google Place public API Key</label></th>';
        $retourHtml .= '<td><input name="osm_poi_google_key" type="text" id="osm_poi_google_key" class="regular-text" value="'.get_option("osm_poi_google_key").'" /></td></tr>';


        $map_style = get_option("osm_poi_map_style");
        if($map_style == "") $$map_style = "HERE.normalDay";
        $retourHtml .= '<tr><th scope="row"><label>Map style</label></th>';
        $retourHtml .= '<td><input name="osm_poi_map_style" type="text" id="osm_poi_map_style" class="regular-text" value="'.$map_style.'" /> <small><a target="blank" href="https://leaflet-extras.github.io/leaflet-providers/preview/">List here</a></td></tr>';

               

		$list_pois = OSM_POI_LIST;
		$list_pois["main"] = "Main";
        foreach($list_pois as $slug => $name){

        	$attachment_name = "osm_poi_attachement_".$slug;

        	$retourHtml .= '<tr><th scope="row">Marker &laquo; '.$slug.' &raquo;</th>';
	        wp_enqueue_media();
	        $attachment_id 	= get_option( $attachment_name, "" );
	        $img_src 		= wp_get_attachment_url($attachment_id);

	        if($img_src === false) $img_src = OSM_POI_URL."/images/pois/marker-".$slug.".png";

	        $retourHtml .= '<td>
	        					<div style="display: inline-block;" class="image-preview-wrapper">
	        						<img id="image-preview-'.$slug.'" src="'.$img_src.'" >
								</div>
								<input id="upload_image_button_'.$slug.'" type="button" class="button" value="Change" />
								<input type="hidden" name="'.$attachment_name.'" id="'.$attachment_name.'" value="'.$attachment_id.'">
	        				</td></tr>';


        }





        $retourHtml .= '</tbody>';
        $retourHtml .= '</table>';


 

        $retourHtml .= '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save settings"></p>';

        $retourHtml .= '</form>';

        $retourHtml .= '</div>'; // Fin wrapper

        echo $retourHtml;		

	}




	public function print_scripts() {

		$markers = array("main");
		$list_pois = OSM_POI_LIST;
        foreach($list_pois as $slug => $name) $markers[] = $slug;



		?>
		<script type='text/javascript'>
			jQuery( document ).ready( function( $ ) {

                <?php
                foreach($markers as $slug){

                	$my_saved_attachment_post_id 	= get_option( 'osm_poi_attachement_'.$slug, "''" );
                ?>

                	// Uploading files
					var file_frame_<?php echo $slug; ?>;
	                if( wp.media == null ) return;


					var wp_media_post_id_<?php echo $slug; ?> = wp.media.model.settings.post.id; // Store the old id
					var set_to_post_id_<?php echo $slug; ?> = <?php echo $my_saved_attachment_post_id; ?>; // Set this
					

					jQuery('#upload_image_button_<?php echo $slug; ?>').on('click', function( event ){
						
						event.preventDefault();
						// If the media frame already exists, reopen it.
						if ( file_frame_<?php echo $slug; ?> ) {
							// Set the post ID to what we want
							file_frame_<?php echo $slug; ?>.uploader.uploader.param( 'post_id', set_to_post_id_<?php echo $slug; ?> );
							// Open frame
							file_frame_<?php echo $slug; ?>.open();
							return;
						}
						else {
							// Set the wp.media post id so the uploader grabs the ID we want when initialised
							wp.media.model.settings.post.id = set_to_post_id_<?php echo $slug; ?>;
						}

						// Create the media frame.
						file_frame_<?php echo $slug; ?> = wp.media.frames.file_frame_<?php echo $slug; ?> = wp.media({
							title: 'Select a image to upload',
							button: {
								text: 'Set as "<?php echo $slug; ?>" marker',
							},
							multiple: false	// Set to true to allow multiple files to be selected
						});

						// When an image is selected, run a callback.
						file_frame_<?php echo $slug; ?>.on( 'select', function() {
							
							// We set multiple to false so only get one image from the uploader
							attachment = file_frame_<?php echo $slug; ?>.state().get('selection').first().toJSON();
							
							// Do something with attachment.id and/or attachment.url here
							$( '#image-preview-<?php echo $slug; ?>' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
							$( '#osm_poi_attachement_<?php echo $slug; ?>' ).val( attachment.id );

							// Restore the main post ID
							wp.media.model.settings.post.id = wp_media_post_id_<?php echo $slug; ?>;
						});

						// Finally, open the modal
						file_frame_<?php echo $slug; ?>.open();

					});
					// Restore the main ID when the add media button is pressed
					jQuery( 'a.add_media' ).on( 'click', function() {
						wp.media.model.settings.post.id = wp_media_post_id_<?php echo $slug; ?>;
					});


				<?php
				}
				?>

			});
		</script>
		<?php
	}


	public function save_configuration($params)
    {

        // Gestion des checkboxes
        foreach ($params as $nom_option => $valeur_option) {
            $nom_option     = nettoie($nom_option);

            if ($valeur_option <> '') {
                update_option($nom_option, $valeur_option, false);
            } else {
                delete_option($nom_option);
            }
        }

        return;
    }


}