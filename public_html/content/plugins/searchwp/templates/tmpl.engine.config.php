<?php

if( !defined( 'ABSPATH' ) ) die();

/**
 * Returns the saved weight (or a default if there's no saved data)
 *
 * @param array $weights
 * @param string $type
 * @param $subtype
 * @return int
 * @since 1.0
 */
function searchwpGetEngineWeight( $weights = array(), $type = 'title', $subtype = null )
{
	$weight = 1;
	if( !is_array( $weights ) ) $weights = array();

	switch( $type )
	{
		case 'title':
			$weight = isset( $weights['title'] ) ? floatval( $weights['title'] ) : 20;
			break;

		case 'content':
			$weight = isset( $weights['content'] ) ? floatval( $weights['content'] ) : 2;
			break;

		case 'slug':
			$weight = isset( $weights['slug'] ) ? floatval( $weights['slug'] ) : 10;
			break;

		case 'tax':
			$weight = 5;
			if( is_string( $subtype ) && isset( $weights['tax'][$subtype] ) ) {
				$weight = floatval( $weights['tax'][$subtype] );
			}
			break;

		case 'excerpt':
			$weight = isset( $weights['excerpt'] ) ? floatval( $weights['excerpt'] ) : 6;
			break;

		case 'comment':
			$weight = isset( $weights['comment'] ) ? floatval( $weights['comment'] ) : 1;
			break;
	}

	return $weight;
}


/**
 * Echoes the markup for the search engine settings UI
 *
 * @param string $engine The engine name
 * @return bool
 * @since 1.0
 */
function searchwpEngineSettingsTemplate( $engine = 'default' ) {
	global $searchwp;

	$settings = $searchwp->settings;
	$engine = sanitize_key( $engine );

	if( $engine != 'default' && is_array( $settings ) && !array_key_exists( 'engines', $settings ) ) {
		if( !array_key_exists( $engine, $settings['engines'] ) ) {
			return false;
		}
	}

	$engineSettings = isset( $settings['engines'] ) && isset( $settings['engines'][$engine] ) ? $settings['engines'][$engine] : false;

	// retrieve list of all post types
	$post_types = array_merge(
		array(
			'post'          => 'post',
			'page'          => 'page',
			'attachment'    => 'attachment',
		),
		get_post_types(
			array(
				'exclude_from_search'   => false,
				'_builtin'              => false
			)
		)
	);

	if( 'swpengine' == $engine ) {
		$engine = '{{ swp.engine }}';
	}

	?>

<div class="swp-tabbable swp-group">
	<ul class="swp-nav swp-tabs">
		<?php foreach( $post_types as $post_type ) : $post_type = get_post_type_object( $post_type ); ?>
			<?php if( $post_type->name != 'attachment' ) : ?>
				<li data-swp-engine="swp-engine-<?php echo $engine; ?>-<?php echo $post_type->name; ?>" class="">
					<span>
						<?php $enabled = !empty( $engineSettings[$post_type->name]['enabled'] ); ?>
						<input type="checkbox" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][enabled]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>" value="1" <?php checked( $enabled ); ?>/>
						<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>"><?php echo $post_type->labels->name; ?></label>
					</span>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
		<li data-swp-engine="swp-engine-<?php echo $engine; ?>-attachment" class="">
			<span>
				<?php $enabled = !empty( $engineSettings['attachment']['enabled'] ); ?>
				<input type="checkbox" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][attachment][enabled]" id="swp_engine_<?php echo $engine; ?>_attachment" value="1" <?php checked( $enabled ); ?>/>
				<label for="swp_engine_<?php echo $engine; ?>_posts"><?php _e( 'Media', 'searchwp' ); ?></label>
			</span>
		</li>
	</ul>
	<div class="swp-tab-content">
		<?php foreach( $post_types as $post_type ) : $post_type = get_post_type_object( $post_type ); ?>
			<div class="swp-engine swp-engine-<?php echo $engine; ?> swp-group swp-tab-pane" id="swp-engine-<?php echo $engine; ?>-<?php echo $post_type->name; ?>">
				<?php $weights = !empty( $engineSettings[$post_type->name]['weights'] ) ? $engineSettings[$post_type->name]['weights'] : array(); ?>
				<div class="swp-tooltip-content" id="swp-tooltip-weights-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">
					<?php _e( 'These values add weight to results.<br /><br />A weight of 1 is neutral<br />Between 0 &amp; 1 lowers result weight<br />Over 1 increases result weight<br />Zero omits the result<br />-1 excludes matches', 'searchwp' ); ?>
				</div>
				<!-- <p class="description" style="padding-bottom:10px;"><?php _e( 'Applicable entries', 'searchwp' ); ?>: <?php $count_posts = wp_count_posts( $post_type->name ); echo 'attachment' != $post_type->name ? $count_posts->publish : $count_posts->inherit; ?></p> -->
				<div class="swp-engine-weights">
					<table>
						<colgroup>
							<col class="swp-col-content-type" />
							<col class="swp-col-content-weight" />
						</colgroup>
						<thead>
							<tr>
								<th><?php _e( 'Content Type', 'searchwp' ); ?></th>
								<th><?php _e( 'Weight', 'searchwp' ); ?> <a class="swp-tooltip" href="#swp-tooltip-weights-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">?</a></th>
							</tr>
						</thead>
						<tbody>

							<?php if( post_type_supports( $post_type->name, 'title' ) ) : ?>
								<tr>
									<td><label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_title"><?php _e( 'Title', 'searchwp' ); ?></label></td>
									<td><input type="number" min="-1" step="0.1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][title]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_title" value="<?php echo searchwpGetEngineWeight( $weights, 'title' ); ?>" /></td>
								</tr>
							<?php endif; ?>
							<?php if( post_type_supports( $post_type->name, 'editor' ) || $post_type->name == 'attachment' ) : ?>
								<tr>
									<td><label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_content"><?php if( $post_type->name != 'attachment' ) { _e( 'Content', 'searchwp' ); } else { _e( 'Description', 'searchwp' ); } ?></label></td>
									<td><input type="number" min="-1" step="0.1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][content]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_content" value="<?php echo searchwpGetEngineWeight( $weights, 'content' ); ?>" /></td>
								</tr>
							<?php endif; ?>
							<?php if( $post_type->name == 'page' || $post_type->publicly_queryable ) : ?>
								<tr>
									<td><label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_slug"><?php _e( 'Slug', 'searchwp' ); ?></label></td>
									<td><input type="number" min="-1" step="0.1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][slug]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_slug" value="<?php echo searchwpGetEngineWeight( $weights, 'slug' ); ?>" /></td>
								</tr>
							<?php endif; ?>
							<?php
							$taxonomies = get_object_taxonomies( $post_type->name );
							if( is_array( $taxonomies ) && count( $taxonomies ) ) :
								foreach( $taxonomies as $taxonomy ) :
									if( $taxonomy != 'post_format' ) : // we don't want Post Formats here
										$taxonomy = get_taxonomy( $taxonomy );
										?>
										<tr>
											<td><label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_tax_<?php echo $taxonomy->name; ?>"><?php echo $taxonomy->labels->name; ?></label></td>
											<td><input type="number" min="-1" step="0.1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][tax][<?php echo $taxonomy->name; ?>]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_tax_<?php echo $taxonomy->name; ?>" value="<?php echo searchwpGetEngineWeight( $weights, 'tax', $taxonomy->name ); ?>" /></td>
										</tr>
									<?php endif; endforeach; endif; ?>
							<?php if( post_type_supports( $post_type->name, 'excerpt' ) || $post_type->name == 'attachment' ) : ?>
								<tr>
									<td><label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_excerpt"><?php if( $post_type->name != 'attachment' ) { _e( 'Excerpt', 'searchwp' ); } else { _e( 'Caption', 'searchwp' ); } ?></label></td>
									<td><input type="number" min="-1" step="0.1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][excerpt]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_excerpt" value="<?php echo searchwpGetEngineWeight( $weights, 'excerpt' ); ?>" /></td>
								</tr>
							<?php endif; ?>
							<?php if( post_type_supports( $post_type->name, 'comments' ) && $post_type->name != 'attachment' ) : ?>
								<?php if( apply_filters( 'searchwp_index_comments', true ) ) : ?>
									<tr>
										<td><label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_comment"><?php _e( 'Comments', 'searchwp' ); ?></label></td>
										<td><input type="number" min="-1" step="0.1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][comment]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_weights_comment" value="<?php echo searchwpGetEngineWeight( $weights, 'comment' ); ?>" /></td>
									</tr>
								<?php endif; ?>
							<?php endif; ?>

							<?php if( 'attachment' == $post_type->name ) : ?>
								<?php
									// check to see if the PDF weight has already been stored
									// if not, use default content weight
									$pdfweight = searchwpGetEngineWeight( $weights, 'content' );
									if( isset( $engineSettings[$post_type->name]['weights']['cf'] ) && is_array( $engineSettings[$post_type->name]['weights']['cf'] ) && !empty( $engineSettings[$post_type->name]['weights']['cf'] ) ) {
										$cfWeights = $engineSettings[$post_type->name]['weights']['cf'];
										foreach( $cfWeights as $cfFlag => $cfWeight ) {
											if( $cfWeight['metakey'] == SEARCHWP_PREFIX . 'content' ) {
												$pdfweight = $cfWeight['weight'];
												break;
											}
										}
									}

									$arrayFlag = uniqid( 'swpp' );
								?>
								<tr class="swp-custom-field">
									<td class="swp-custom-field-select">
										<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_<?php echo $arrayFlag; ?>_weight"><?php _e( 'Document (PDF) content (when applicable)', 'searchwp' ); ?></label>
									</td>
									<td>
										<input type="hidden" style="display:none;" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][cf][<?php echo $arrayFlag; ?>][metakey]" value="<?php echo SEARCHWP_PREFIX; ?>content" />
										<input type="number" min="-1" step="0.1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][cf][<?php echo $arrayFlag; ?>][weight]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_<?php echo $arrayFlag; ?>_weight" value="<?php echo $pdfweight; ?>" />
									</td>
								</tr>
							<?php endif; ?>

							<tr class="swp-custom-fields-heading">
								<td colspan="2">
									<strong><?php _e( 'Custom Fields', 'searchwp' ); ?></strong>
								</td>
							</tr>

							<?php if( isset( $engineSettings[$post_type->name]['weights']['cf'] ) && is_array( $engineSettings[$post_type->name]['weights']['cf'] ) && !empty( $engineSettings[$post_type->name]['weights']['cf'] ) ) : $cfWeights = $engineSettings[$post_type->name]['weights']['cf']; ?>
							<?php foreach( $cfWeights as $cfFlag => $cfWeight ) : $arrayFlag = uniqid( 'swpp' ); ?>
								<?php if( $cfWeight['metakey'] != SEARCHWP_PREFIX . 'content' ) : /* handled elsewhere specifically */ ?>
									<tr class="swp-custom-field">
										<td class="swp-custom-field-select">
											<select name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][cf][<?php echo $arrayFlag; ?>][metakey]" style="width:80%;">
												<option value="searchwpcfdefault" <?php selected( $cfWeight['metakey'], 'searchwpcfdefault' ); ?>><?php _e( 'Any', 'searchwp' ); ?></option>
												<?php if( !empty( $searchwp->keys ) ) : foreach( $searchwp->keys as $key ) : ?>
													<option value="<?php echo $key; ?>" <?php selected( strtolower( $cfWeight['metakey'] ), strtolower( $key ) ); ?>><?php echo $key; ?></option>
												<?php endforeach; endif; ?>
											</select>
											<a class="swp-delete" href="#">x</a>
										</td>
										<td><input type="number" min="-1" step="0.1" class="small-text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][weights][cf][<?php echo $arrayFlag; ?>][weight]" value="<?php echo $cfWeight['weight']; ?>" /></td>
									</tr>
								<?php endif; ?>
							<?php endforeach; endif; ?>

							<tr>
								<td colspan="2">
									<a class="button swp-add-custom-field" href="#" data-engine="<?php echo $engine; ?>" data-posttype="<?php echo $post_type->name; ?>"><?php _e( 'Add Custom Field', 'searchwp' ); ?></a>
									<a class="swp-tooltip swp-tooltip-custom-field" href="#swp-tooltip-custom-field-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">?</a>
									<div class="swp-tooltip-content" id="swp-tooltip-custom-field-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">
										<?php _e( 'Include Custom Field data in search results. Meta values do not need to be plain strings, available keywords in metadata are extracted and indexed.', 'searchwp' ); ?>
									</div>
								</td>
							</tr>

						</tbody>
					</table>
				</div>
				<div class="swp-engine-options">
					<?php $options = !empty( $engineSettings[$post_type->name]['options'] ) ? $engineSettings[$post_type->name]['options'] : array(); ?>
					<table>
						<tbody>
							<tr>
								<td>
									<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_exclude"><?php _e( 'Exclude IDs: ', 'searchwp' ); ?></label>
								</td>
								<td>
									<?php
										if( ! empty( $options['exclude'] ) && false === strpos( $options['exclude'], ',' ) && ! is_numeric( $options['exclude'] ) ) {
											$options['exclude'] = '';
										}
									?>
									<input type="text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][exclude]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_exclude" placeholder="<?php _e( 'Comma separated IDs', 'searchwp' ); ?>" value="<?php if( ! empty( $options['exclude'] ) ) echo $options['exclude']; ?>" /> <a class="swp-tooltip" href="#swp-tooltip-exclude-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">?</a>
									<div class="swp-tooltip-content" id="swp-tooltip-exclude-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">
										<?php _e( 'Comma separated post IDs. Will be excluded entirely, even if attributed to.', 'searchwp' ); ?>
									</div>
								</td>
							</tr>
							<?php
							$taxonomies = get_object_taxonomies( $post_type->name );
							if( is_array( $taxonomies ) && count( $taxonomies ) ) :
								foreach( $taxonomies as $taxonomy ) {
									$taxonomy = get_taxonomy( $taxonomy );
									$taxonomy_args = array(
										'hide_empty' => false,
									);
									$terms = get_terms( $taxonomy->name, $taxonomy_args );
									if( ! empty( $terms ) ) :
									?>
									<tr>
										<td>
											<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_exclude_<?php echo $taxonomy->name; ?>">
												<?php echo __( 'Exclude ', 'searchwp' ) . $taxonomy->labels->name . ': '; ?>
											</label>
										</td>
										<td>
											<?php
												// retrieve our stored exclusions
												$excluded = isset( $options['exclude_' . $taxonomy->name] ) ? explode( ',', $options['exclude_' . $taxonomy->name] ) : array();
												if( !empty( $excluded ) )
													foreach( $excluded as $excludedKey => $excludedValue )
														 $excluded[$excludedKey] = intval( $excludedValue );
											?>
											<select class="swp-exclude-select" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][exclude_<?php echo $taxonomy->name; ?>][]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_exclude_<?php echo $taxonomy->name; ?>" multiple data-placeholder="<?php _e( 'Leave blank to omit', 'searchwp' ); ?>" style="width:170px;">
												<?php foreach( $terms as $term ) : ?>
													<?php $selected = in_array( $term->term_id, $excluded ) ? ' selected="selected"' : ''; ?>
													<option value="<?php echo $term->term_id; ?>" <?php echo $selected; ?>><?php echo $term->name; ?></option>
												<?php endforeach; ?>
											</select>
											<a class="swp-tooltip" href="#swp-tooltip-exclude-<?php echo $post_type->name; ?>-<?php echo $taxonomy->name; ?>">?</a>
											<div class="swp-tooltip-content" id="swp-tooltip-exclude-<?php echo $post_type->name; ?>-<?php echo $taxonomy->name; ?>">
												<?php _e( 'Entries with these will be excluded entirely, even if attributed to.', 'searchwp' ); ?>
											</div>
										</td>
									</tr>
								<?php endif; }
							endif; ?>
							<?php /*
							<p>
								<?php $enabled = !empty( $options['other_statuses'] ); ?>
								<input type="checkbox" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][other_statuses]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_other_statuses" value="1" <?php checked( $enabled ); ?>/>
								<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_other_statuses"><?php _e( 'Include additional statuses: ', 'searchwp' ); ?></label>
								<input type="text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][statuses]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_statuses" placeholder="<?php _e( 'Comma separated statuses', 'searchwp' ); ?>" value="<?php if( !empty( $options['statuses'] ) ) echo $options['statuses']; ?>" /> <a class="swp-tooltip" title="<?php _e( 'By default, only published entries are indexed. You can include additional statuses here. Ensure specified statuses are properly implemented.', 'searchwp' ); ?>">?</a>
							</p>
							<p>
								<?php $enabled = !empty( $options['limit_formats'] ); ?>
								<input type="checkbox" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][limit_formats]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_limit_formats" value="1" <?php checked( $enabled ); ?>/>
								<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_limit_formats"><?php _e( 'Limit to Post Formats: ', 'searchwp' ); ?></label>
								<input type="text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][formats]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_formats" placeholder="<?php _e('Comma separated formats', 'searchwp' ); ?>" value="<?php if( !empty( $options['formats'] ) ) echo $options['formats']; ?>" /> <a class="swp-tooltip" title="<?php _e( 'By default, all post formats are indexed. You can limit that here. Ensure specified formats have been properly implemented.', 'searchwp' ); ?>">?</a>
							</p>
							<p>
								<?php $enabled = !empty( $options['shortcodes'] ); ?>
								<input type="checkbox" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][shortcodes]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_shortcodes" value="1" <?php echo checked( $enabled ); ?>/>
								<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_shortcodes"><?php _e( 'Process Shortcodes', 'searchwp' ); ?></label>
								<a class="swp-tooltip" title="<?php _e( 'Expand Shortcodes before indexing. By default, Shortcodes are not processed.', 'searchwp' ); ?>">?</a>
							</p>
							<p>
								<?php $enabled = !empty( $options['logged_in'] ); ?>
								<input type="checkbox" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][logged_in]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_logged_in" value="1" <?php echo checked( $enabled ); ?>/>
								<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_logged_in"><?php _e( 'Index as though logged in', 'searchwp' ); ?></label>
								<a class="swp-tooltip" title="<?php _e( "Some conditional logic outputs different content if you're logged in. Enabling this option will force that condition to be true.", 'searchwp' ); ?>">?</a>
							</p>
							*/ ?>
							<?php if( $post_type->name == 'attachment' ) : ?>
								<tr>
									<td>
										<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_mimes">
											<?php echo __( 'Limit File Type(s) to', 'searchwp' ) . ': '; ?>
										</label>
									</td>
									<td>
										<?php
										// TODO: needs better storage method
										$mimes = array(
											__( 'All Documents', 'searchwp' ),
											__( 'PDFs', 'searchwp' ),
											__( 'Plain Text', 'searchwp' ),
											__( 'Images', 'searchwp' ),
											__( 'Video', 'searchwp' ),
											__( 'Audio', 'searchwp' ),
										);
										// retrieve our stored exclusions
										$limitedMimes = isset( $options['mimes'] ) ? explode( ',', $options['mimes'] ) : array();
										if( !empty( $limitedMimes ) )
											foreach( $limitedMimes as $limitedMimeKey => $limitedMimeValue )
												$limitedMimes[$limitedMimeKey] = intval( $limitedMimeValue );
										?>
										<select class="swp-exclude-select" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][mimes][]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_mimes" multiple data-placeholder="<?php _e( 'Leave blank to omit', 'searchwp' ); ?>" style="width:170px;">
											<?php for( $i = 0; $i < count( $mimes ); $i++ ) : ?>
												<?php $selected = in_array( $i, $limitedMimes ) ? ' selected="selected"' : ''; ?>
												<option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $mimes[$i]; ?></option>
											<?php endfor; ?>
										</select>
										<a class="swp-tooltip" href="#swp-tooltip-limit-<?php echo $post_type->name; ?>-mime-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">?</a>
										<div class="swp-tooltip-content" id="swp-tooltip-limit-<?php echo $post_type->name; ?>-mime-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">
											<?php _e( 'If populated, Media results will be limited to these Media types', 'searchwp' ); ?>
										</div>
									</td>
								</tr>
							<?php endif; ?>
							<?php if( $post_type->name == 'attachment' || apply_filters( "searchwp_enable_parent_attribution_{$post_type->name}", false ) ) : ?>
								<tr>
									<td><?php _e( 'Attribute post parent', 'searchwp' ); ?></td>
									<td>
										<?php $enabled = !empty( $options['parent'] ); ?>
										<input type="checkbox" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][parent]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_parent" value="1" <?php checked( $enabled ); ?>/>
										<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_parent"><?php _e( 'Enabled', 'searchwp' ); ?></label>
										<a class="swp-tooltip" href="#swp-tooltip-parent-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">?</a>
										<div class="swp-tooltip-content" id="swp-tooltip-parent-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">
											<?php _e( "When enabled, search weights will be applied to the post parent, not the post GUID", 'searchwp' ); ?>
										</div>
									</td>
								</tr>
							<?php elseif( apply_filters( "searchwp_enable_attribution_{$post_type->name}", true ) ) : ?>
								<tr>
									<td>
										<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_attribute"><?php _e( 'Attribute search results to ', 'searchwp' ); ?></label>
									</td>
									<td>
										<input type="number" min="1" step="1" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][attribute_to]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_attribute_to" value="<?php if( !empty( $options['attribute_to'] ) ) echo $options['attribute_to']; ?>" placeholder="<?php _e( 'Single post ID', 'searchwp' ); ?>" />
										<a class="swp-tooltip" href="#swp-tooltip-attribute-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">?</a>
										<div class="swp-tooltip-content" id="swp-tooltip-attribute-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">
											<?php _e( "<strong>Expects single post ID</strong><br/>If permalinks for this post type should not be included in search results, you can have it's search weight count toward another post ID.", 'searchwp' ); ?>
										</div>
									</td>
								</tr>
							<?php endif; ?>
							<tr>
								<td><?php _e( 'Use keyword stem', 'searchwp' ); ?></td>
								<td>
									<?php $enabled = !empty( $options['stem'] ); ?>
									<input type="checkbox" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php echo $engine; ?>][<?php echo $post_type->name; ?>][options][stem]" id="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_stem" value="1" <?php checked( $enabled ); ?>/>
									<label for="swp_engine_<?php echo $engine; ?>_<?php echo $post_type->name; ?>_stem"><?php _e( 'Enabled', 'searchwp' ); ?></label>
									<a class="swp-tooltip" href="#swp-tooltip-stem-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">?</a>
									<div class="swp-tooltip-content" id="swp-tooltip-stem-<?php echo $engine; ?>_<?php echo $post_type->name; ?>">
										<?php _e( "<em>May increase search latency</em><br />For example: when enabled, searches for <strong>run</strong> and <strong>running</strong> will generate the same results. When disabled, results may be different.", 'searchwp' ); ?>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<?php }
