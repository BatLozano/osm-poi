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


        $retourHtml .= '<tr><th scope="row">Default marker</th>';
        wp_enqueue_media();
        $attachment_id 	= get_option( 'osm_poi_attachement_id', "" );
        $img_src 		= wp_get_attachment_url($attachment_id);

        if($img_src === false) $img_src = OSM_POI_URL."/images/marker.png";

        $retourHtml .= '<td>
        					<div style="display: inline-block;" class="image-preview-wrapper">
        						<img id="image-preview" src="'.$img_src.'" width="32" height="32" style="max-height: 32px; width: 32px;">
							</div>
							<input id="upload_image_button" type="button" class="button" value="Change" />
							<input type="hidden" name="osm_poi_attachement_id" id="osm_poi_attachement_id" value="'.$attachment_id.'">
        				</td></tr>';

        $retourHtml .= '</tbody>';
        $retourHtml .= '</table>';

 

        $retourHtml .= '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save settings"></p>';

        $retourHtml .= '</form>';

        $retourHtml .= '</div>'; // Fin wrapper

        echo $retourHtml;		

	}




	public function print_scripts() {

		$my_saved_attachment_post_id = get_option( 'osm_poi_attachement_id', 0 );

		?>
		<script type='text/javascript'>
			jQuery( document ).ready( function( $ ) {
				// Uploading files
				var file_frame;
                if( wp.media == null ) return;
				var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
				var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this
				jQuery('#upload_image_button').on('click', function( event ){
					event.preventDefault();
					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						// Set the post ID to what we want
						file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
						// Open frame
						file_frame.open();
						return;
					} else {
						// Set the wp.media post id so the uploader grabs the ID we want when initialised
						wp.media.model.settings.post.id = set_to_post_id;
					}
					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						title: 'Select a image to upload',
						button: {
							text: 'Use this image',
						},
						multiple: false	// Set to true to allow multiple files to be selected
					});
					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {
						// We set multiple to false so only get one image from the uploader
						attachment = file_frame.state().get('selection').first().toJSON();
						// Do something with attachment.id and/or attachment.url here
						$( '#image-preview' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
						$( '#osm_poi_attachement_id' ).val( attachment.id );
						// Restore the main post ID
						wp.media.model.settings.post.id = wp_media_post_id;
					});
						// Finally, open the modal
						file_frame.open();
				});
				// Restore the main ID when the add media button is pressed
				jQuery( 'a.add_media' ).on( 'click', function() {
					wp.media.model.settings.post.id = wp_media_post_id;
				});
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