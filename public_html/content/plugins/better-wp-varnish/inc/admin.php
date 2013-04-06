<?php

if (!class_exists( 'bwpv_admin' ) ) {

	class bwpv_admin extends bit51_bwpv {
		
		/**
		 * Initialize admin function
		 */
		function __construct() {
			
			//add scripts and css
			add_action( 'admin_print_scripts', array( &$this, 'config_page_scripts' ) );
			add_action( 'admin_print_styles', array( &$this, 'config_page_styles' ) );
		
			//add menu items
			add_action( 'admin_menu', array( &$this, 'register_settings_page' ) );
		
			//add settings
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
		
			//add action link
			add_filter( 'plugin_action_links', array( &$this, 'add_action_link' ), 10, 2 );
		
			//add donation reminder
			add_action( 'admin_init', array( &$this, 'ask' ) );
			
		}
	
		/**
		 * Register page settings
		 */
		function register_settings_page() {

			add_options_page( __( $this->pluginname, $this->hook ), __( $this->pluginname, $this->hook ), $this->accesslvl, $this->hook, array( &$this,'bwpv_admin_init' ) );

		}	
		
		/**
		 * Register admin page main content
		 * To add more boxes to the admin page add a 2nd inner array item with title and callback function or content
		 */
		function bwpv_admin_init() {

			$this->admin_page( $this->pluginname . ' ' . __( 'Options', $this->hook ), 

				array(

					array( __( 'Instructions', $this->hook), 'install_instructions' ), //primary admin page content
					array( __( 'General Options', $this->hook), 'general_options' ), //primary admin page content

				)

			);

		}
		
		/**
		 * Create instructions block
		 */
		function install_instructions() {
			?>
			<p><?php _e( 'Simply enter your varnish server address and port below and select enable. Caches will automatically be cleared where appropriate (new content, comments, etc) and you can manually clear your cache via the admin bar.', $this->hook ); ?></p>
			<?php
		}
		
		/**
		 * Create admin page main content
		 */
		function general_options() {

			global $bwpvoptions, $bit51bwpv;

			if ( isset( $_GET['flush'] ) && isset( $_GET['id'] ) && wp_verify_nonce( filter_var( $_REQUEST['_wpnonce'], FILTER_SANITIZE_STRING ), 'bwpv-nonce') ) {
	
				if ( intval( $_GET['flush'] == filter_var( 'all', FILTER_SANITIZE_STRING ) ) ) {

					$bit51bwpv->purgeAll();

				} else {

					$bit51bwpv->purgePost( filter_var( $_GET['ID'], FILTER_SANITIZE_STRING ) );					

				}

			}

			?>
			<form method="post" action="options.php">
			<?php settings_fields( 'bit51_bwpv_options' ); //use main settings group ?>
				<table class="form-table">
					<tr valign="top">
						<td>
							<input type="checkbox" name="bit51_bwpv[enabled]" id="enabled" value="1" <?php if ( $bwpvoptions['enabled'] == 1 ) echo "checked"; ?> /> <label for="buffer"><?php _e( 'Enable Varnish Cache Purge', $this->hook ); ?></label><br />
							<label for"address"><?php _e( 'Server Address', $this->hook ); ?></label> <input name="bit51_bwpv[address]" id="header" value="<?php echo $bwpvoptions['address']; ?>" type="text"><br />
							<label for"port"><?php _e( 'Server Address', $this->hook ); ?></label> <input name="bit51_bwpv[port]" id="port" value="<?php echo $bwpvoptions['port']; ?>" type="text"><br />
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" /></p>
			</form>
			<?php
		}

		/**
		 * Validate input
		 */
		function bwpv_val_options( $input ) {
			
			//make sure boolean options are set
			$input['enabled'] = isset( $input['enabled'] ) ? $input['enabled'] : '0';

			$input['address'] = sanitize_text_field( $input['address'] );
			$input['port'] = intval( sanitize_text_field( $input['port'] ) );
		    
		    return $input;
		}
		
	}

}
