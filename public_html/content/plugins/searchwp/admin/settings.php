<?php

global $wpdb;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'Invalid request', 'searchwp' ) );
}

// retrieve custom field keys to include in the Custom Fields weight table select
$this->keys = $wpdb->get_col( "
	SELECT meta_key
	FROM $wpdb->postmeta
	WHERE meta_key != '_" . SEARCHWP_PREFIX . "indexed'
	AND meta_key != '" . SEARCHWP_PREFIX . "content'
	AND meta_key != '_" . SEARCHWP_PREFIX . "needs_remote'
	AND meta_key != '_" . SEARCHWP_PREFIX . "skip'
	AND meta_key NOT LIKE '_oembed_%'
	GROUP BY meta_key
" );

// allow devs to filter this list
$this->keys = array_unique( apply_filters( 'searchwp_custom_field_keys', $this->keys ) );

// sort the keys alphabetically
if ( $this->keys ) {
	natcasesort( $this->keys );
} else {
	$this->keys = array();
}

?><div class="wrap">
		<div id="icon-searchwp" class="icon32">
			<img src="<?php echo trailingslashit( $this->url ); ?>assets/images/searchwp@2x.png" alt="SearchWP" width="21" height="32" />
		</div>

		<form action="options.php" method="post">

			<div class="swp-wp-settings-api">
				<?php do_settings_sections( $this->textDomain ); ?>
				<?php settings_fields( SEARCHWP_PREFIX . 'settings' ); ?>
			</div>

			<script type="text/html" id="tmpl-swp-custom-fields">
				<tr class="swp-custom-field">
					<td class="swp-custom-field-select">
						<select name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][{{ swp.engine }}][{{ swp.postType }}][weights][cf][{{ swp.arrayFlag }}][metakey]" style="width:80%;">
							<option value="searchwp cf default"><?php _e( 'Any', 'searchwp' ); ?></option>
									<?php if ( ! empty( $this->keys ) ) : foreach ( $this->keys as $key ) : ?>
										<option value="<?php echo $key; ?>"><?php echo $key; ?></option>
									<?php endforeach; endif; ?>
								</select>
						<a class="swp-delete" href="#">X</a>
					</td>
					<td>
						<input type="number" min="-1" step="1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][{{ swp.engine }}][{{ swp.postType }}][weights][cf][{{ swp.arrayFlag }}][weight]" value="1" />
					</td>
				</tr>
			</script>

			<div class="postbox swp-meta-box swp-default-engine metabox-holder swp-jqueryui">

				<h3 class="hndle"><span><?php _e( 'Default Search Engine', 'searchwp' ); ?></span> <a class="swp-engine-stats" href="<?php echo get_admin_url(); ?>index.php?page=searchwp-stats&tab=default"><?php _e( 'Statistics', 'searchwp' ); ?> &raquo;</a></h3>

				<div class="inside">

					<p><?php _e( 'These settings will override WordPress default searches. Customize which post types are included in search and how much weight each content type receives.', 'searchwp' ); ?>
						<a class="swp-tooltip" href="#swp-tooltip-overview">?</a></p>

					<div class="swp-tooltip-content" id="swp-tooltip-overview">
						<?php _e( "Only checked post types will be included in search results. If a post type isn't displayed, ensure <code>exclude_from_search</code> is set to false when registering it.", 'searchwp' ); ?>
					</div>
					<?php searchwpEngineSettingsTemplate( 'default' ); ?>

				</div>

			</div>

			<div class="postbox swp-meta-box metabox-holder swp-jqueryui">

				<?php
					// we're only going to output one stats link because it'll clue the user in to there being more
					$supplemental_stats_link = '';
					if ( isset( $this->settings['engines'] ) && is_array( $this->settings['engines'] ) && count( $this->settings['engines'] ) ) {
						foreach ( $this->settings['engines'] as $engineFlag => $engine ) {
							if ( isset( $engine['label'] ) && ! empty( $engine['label'] ) ) {
								$supplemental_stats_link = '<a class="swp-engine-stats" href="' . get_admin_url() . 'index.php?page=searchwp-stats&tab=' . urlencode( sanitize_text_field( $engineFlag ) ) . '">' . __( 'Statistics', 'searchwp' ) . ' &raquo;</a>';
								break;
							}
						}
					}
				?>

				<h3 class="hndle"><span><?php _e( 'Supplemental Search Engines', 'searchwp' ); ?></span> <?php echo $supplemental_stats_link; ?></h3>

				<div class="inside">

					<p><?php _e( 'Here you can build supplemental search engines to use in specific sections of your site. When used, the default search engine settings are completely ignored.', 'searchwp' ); ?>
						<a class="swp-tooltip" href="#swp-tooltip-supplemental">?</a></p>

					<div class="swp-tooltip-content" id="swp-tooltip-supplemental">
						<?php _e( "Only checked post types will be included in search results. If a post type isn't displayed, ensure <code>exclude_from_search</code> is set to false when registering it.", 'searchwp' ); ?>
					</div>

					<script type="text/html" id="tmpl-swp-engine">
								<?php searchwpEngineSettingsTemplate( '{{swp.engine}}' ); ?>
							</script>

					<script type="text/html" id="tmpl-swp-supplemental-engine">
								<?php searchwpSupplementalEngineSettingsTemplate( '{{swp.engine}}' ); ?>
							</script>

					<div class="swp-supplemental-engines-wrapper">
						<ul class="swp-supplemental-engines">
							<?php if ( isset( $this->settings['engines'] ) && is_array( $this->settings['engines'] ) && count( $this->settings['engines'] ) ) : ?>
								<?php foreach ( $this->settings['engines'] as $engineFlag => $engine ) : if ( isset( $engine['label'] ) && ! empty( $engine['label'] ) ) : ?>
									<?php searchwpSupplementalEngineSettingsTemplate( $engineFlag, $engine['label'] ); ?>
								<?php endif; endforeach; ?>
							<?php endif; ?>
						</ul>
						<p>
							<a href="#" class="button swp-add-supplemental-engine"><?php _e( 'Add New Supplemental Engine', 'searchwp' ); ?></a>
						</p>
					</div>

				</div>

			</div>

			<div class="swp-settings-footer swp-group">
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<p class="swp-settings-advanced">
						<a href="options-general.php?page=searchwp&amp;nonce=<?php echo wp_create_nonce( 'swpadvanced' ); ?>"><?php _e( 'Advanced', 'searchwp' ); ?></a>
					</p>
				<?php endif; ?>
				<?php submit_button(); ?>
			</div>

		</form>
	</div>
	<script type="text/javascript">
		<?php include dirname( __FILE__ ) . "/../assets/js/searchwp.js"; ?>
	</script>
<?php
