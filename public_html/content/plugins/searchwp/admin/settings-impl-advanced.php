<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SearchWP_Settings_Implementation_Advanced {

	/**
	 * @var array Verified action names
	 */
	private $pending_actions = array();

	/**
	 * Initializer; hook navigation tab (and corresponding view) and any custom functionality
	 */
	function init() {

		// render the 'Advanced' tab on the settings screen
		add_action( 'searchwp_settings_nav_tab', array( $this, 'render_tab_advanced' ), 200 );

		// render the 'Advanced' view when the 'Advanced' tab is viewed
		add_action( 'searchwp_settings_view\advanced', array( $this, 'render_view_advanced' ) );

		// view-specific actions
		add_action( 'searchwp_settings_before\advanced', array( $this, 'maybe_import_settings' ) );

		add_action( 'searchwp_settings_footer', array( $this, 'check_for_db_tables' ) );
	}

	/**
	 * Output a notice on all settings screen if the database tables went missing
	 */
	function check_for_db_tables() {
		$valid_database_environment = SWP()->custom_db_tables_exist();
		if ( ! $valid_database_environment && ( ! isset( $_GET['action'] ) || 'recreate_db_tables' != $_GET['action'] ) ) {
			?>
			<div id="setting-error-swp_custom_tables" class="error notice">
				<p>
					<strong><?php _e( 'Database tables missing! Recreate them on the Advanced Settings screen.', 'searchwp' ); ?></strong>
				</p>
			</div>
		<?php
		}
	}

	/**
	 * Render the tab if current user has appropriate capability
	 */
	function render_tab_advanced() {
		if ( current_user_can( apply_filters( 'searchwp_settings_cap', 'manage_options' ) ) ) {
			searchwp_get_nav_tab( array(
				'tab'   => 'advanced',
				'label' => __( 'Advanced', 'searchwp' ),
			) );
		}
	}

	/**
	 * Fully implements an option in the UI. An option is a checkbox that when toggled (and verified) fires the passed $callback.
	 *
	 * @param $args
	 * @param $callback
	 */
	function implement_option( $args, $callback ) {

	}

	/**
	 * Fully implements an action in the UI. An action is a button that when clicked (and verified) fires the passed $callback.
	 *
	 * @param $args
	 * @param $callback
	 *
	 * @return bool
	 */
	function implement_action( $args, $callback ) {
		$defaults = array(
			'name'                  => '',
			'label'                 => '',
			'heading'               => '',
			'description'           => '',
			'results_message'       => __( 'Done', 'searchwp' ),
			'results_classes'       => 'updated',
			'hide_after_trigger'    => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$nonce_prefix = 'swp_settings_a_';

		// first we process the callback if the proper trigger is in place and the nonce validates
		if ( isset( $_GET['action'] ) && isset( $_GET['nonce'] ) && $args['name'] === $_GET['action'] ) {
			if ( wp_verify_nonce( sanitize_text_field( $_GET['nonce'] ), $nonce_prefix . sanitize_text_field( $args['name'] ) ) && current_user_can( SWP()->settings_cap ) ) {
				$this->pending_actions[] = sanitize_text_field( $args['name'] );
				// fire the callback
				call_user_func_array( $callback , array() );
				?>
				<?php if ( ! empty( $args['results_message'] ) ) : ?>
					<div class="<?php echo esc_attr( $args['results_classes'] ); ?>"><p><?php echo wp_kses_post( $args['results_message'] ); ?></p></div>
				<?php endif; ?>
				<?php

				if ( ! empty( $args['hide_after_trigger'] ) ) {
					return true;
				}
			} else {
				wp_die( __( 'Invalid request', 'searchwp' ) );
			}
		}

		// heading will fall back to label if it's not set
		if ( empty( $args['heading'] ) ) {
			$args['heading'] = $args['label'];
		}

		// every action gets a nonce
		$nonce = wp_create_nonce( $nonce_prefix . sanitize_text_field( $args['name'] ) );
		$the_link = add_query_arg(
			array(
				'action' => $args['name'],
				'nonce'  => $nonce,
			)
		);

		?>
			<div class="postbox swp-meta-box metabox-holder searchwp-settings-action">
				<h3 class="hndle">
					<span><?php echo wp_kses_post( $args['heading'] ); ?></span>
				</h3>
				<div class="inside">
					<p><?php echo wp_kses_post( $args['description'] ); ?></p>
					<p><a class="button" style="vertical-align:middle;" id="swp-indexer-<?php echo esc_attr( $args['name'] ); ?>" href="<?php echo esc_url( $the_link ); ?>"><?php echo esc_html( $args['label'] ); ?></a></p>
				</div>
			</div>
		<?php

		return true;
	}

	/**
	 * Render view callback
	 */
	function render_view_advanced() {
		global $wpdb;
		?>
		<div class="searchwp-advanced-settings-wrapper swp-group">
			<div class="searchwp-advanced-settings-actions">
				<div class="searchwp-emergency-actions swp-group">
					<?php
					$valid_database_environment = SWP()->custom_db_tables_exist();
					if ( ! $valid_database_environment ) {
						$this->implement_action( array(
							'name'                  => 'recreate_db_tables',
							'label'                 => __( 'Recreate Database Tables', 'searchwp' ),
							'description'           => __( "SearchWP's database tables cannot be found. This may happen if a site migration was incomplete. Recreate the tables and initiate an index build.", 'searchwp' ),
							'results_message'       => sprintf( __( 'Database tables created! <a href="%s">Rebuild index &raquo;</a>', 'searchwp' ), admin_url( 'options-general.php?page=searchwp' ) ),
							'hide_after_trigger'    => true,
						), array( $this, 'recreate_db_tables' ) );
						?>
					<?php } ?>
				</div>
				<div class="searchwp-common-actions swp-group">
					<?php
					$this->implement_action( array(
						'name'              => 'index_reset',
						'label'             => __( 'Reset Index', 'searchwp' ),
						'description'       => __( '<strong>Completely</strong> empty the index. <em>Search statistics will be left as is.</em>', 'searchwp' ),
						'results_message'   => sprintf( __( 'The index <strong>has been reset</strong>. <a href="%s">Rebuild index &raquo;</a>', 'searchwp' ), admin_url( 'options-general.php?page=searchwp' ) ),
					), array( $this, 'reset_index' ) );

					$this->implement_action( array(
						'name'              => 'indexer_wake',
						'label'             => __( 'Wake Up Indexer', 'searchwp' ),
						'description'       => __( 'If the indexer appears to have stalled, try waking it up.', 'searchwp' ),
						'results_message'   => sprintf( __( 'Attempted to wake up the indexer. <a href="%s">View progress &raquo;</a>', 'searchwp' ), admin_url( 'options-general.php?page=searchwp' ) ),
					), array( $this, 'indexer_wake' ) );
					?>
				</div>
				<p class="searchwp-show-less-common-actions">
					<a class="button" href="#"><?php _e( 'Show More', 'searchwp' ); ?></a>
				</p>
				<div class="searchwp-less-common-actions swp-group">
				<?php
				$this->implement_action( array(
					'name'              => 'stats_reset',
					'label'             => __( 'Reset Statistics', 'searchwp' ),
					'description'       => __( '<strong>Completely</strong> reset your Search Statistics. <em>Existing index will be left as is.</em>', 'searchwp' ),
					'results_message'   => __( 'Search statistics reset', 'searchwp' ),
				), array( $this, 'reset_stats' ) );

				$this->implement_action( array(
					'name'              => 'indexer_toggle',
					'label'             => __( 'Toggle Indexer', 'searchwp' ),
					'description'       => __( 'Toggle the indexer status. It will pick up where it left off when re-enabled.', 'searchwp' ),
					'results_message'   => false,
				), array( $this, 'indexer_toggle' ) );

				$this->implement_action( array(
					'name'              => 'conflict_notices_reset',
					'label'             => __( 'Restore Conflict Notices', 'searchwp' ),
					'description'       => __( 'Restore all dismissed conflict notifications.', 'searchwp' ),
					'results_message'   => __( 'Conflict notices restored', 'searchwp' ),
				), array( $this, 'conflict_notices_reset' ) );

				$nuke_on_delete = searchwp_get_setting( 'nuke_on_delete' );
				$nuke_on_delete = empty( $nuke_on_delete ) ? false : true;
				$this->implement_action( array(
					'name'              => 'toggle_nuke_on_delete',
					'label'             => __( 'Toggle Nuke on Delete' ),
					'heading'           => $nuke_on_delete && ! isset( $_GET['action'] ) ? __( 'Nuke on Delete' ) . '<span class="description" style="display:inline-block;padding-left:0.5em;padding-right:1em;color:red;text-transform:uppercase;">' . __( 'Enabled' ) . '</span>' : __( 'Nuke on Delete' ),
					'description'       => __( 'Remove <strong>all traces</strong> of SearchWP upon plugin deletion (including index).', 'searchwp' ),
					'results_message'   => false,
				), array( $this, 'toggle_nuke_on_delete' ) );
				?>
				</div>
			</div>
			<div class="searchwp-advanced-settings-stats">
				<div class="postbox swp-meta-box metabox-holder searchwp-settings-stats">
					<h3 class="hndle">
						<span><?php _e( 'Index Statistics', 'searchwp' ); ?></span>
					</h3>
					<?php $stats = SWP()->settings['stats']; ?>
					<div class="inside">
						<p><?php echo sprintf( __( 'The indexer reacts to edits made and will apply updates accordingly. <a href="%s" target="_BLANK">More information &raquo;</a>', 'searchwp' ), 'https://searchwp.com/docs/kb/how-searchwp-works/' ); ?></p>
						<table class="searchwp-data-vis" cellpadding="0" cellspacing="0">
							<tbody>
								<?php if ( isset( $stats['last_activity'] ) ) : ?>
									<tr>
										<th><?php _e( 'Last Activity', 'searchwp' ); ?></th>
										<td>
											<?php echo esc_html( date_i18n( get_option( 'date_format' ), $stats['last_activity'] ) ); ?>
											<?php echo esc_html( date( 'H:i:s', $stats['last_activity'] ) ); ?>
										</td>
									</tr>
								<?php endif; ?>
								<?php if ( isset( $stats['done'] ) ) : ?>
									<tr>
										<th><?php _e( 'Indexed', 'searchwp' ); ?></th>
										<td><code><?php echo absint( $stats['done'] ); ?></code> <?php echo 1 == absint( $stats['done'] ) ? __( 'entry', 'searchwp' ) : __( 'entries', 'searchwp' ); ?></td>
									</tr>
								<?php endif; ?>
								<?php if ( isset( $stats['remaining'] ) ) : ?>
									<tr>
										<th><?php _e( 'Unindexed', 'searchwp' ); ?></th>
										<td><code><?php echo absint( $stats['remaining'] ); ?></code> <?php echo 1 == absint( $stats['remaining'] ) ? __( 'entry', 'searchwp' ) : __( 'entries', 'searchwp' ); ?></td>
									</tr>
								<?php endif; ?>
								<?php
									$indexer = new SearchWPIndexer();
									$row_count = $indexer->get_main_table_row_count();
								?>
								<tr>
									<th><?php _e( 'Main row count', 'searchwp' ); ?></th>
									<td><code><?php echo absint( $row_count ); ?></code> <?php echo 1 == absint( $row_count ) ? __( 'row', 'searchwp' ) : __( 'rows', 'searchwp' ); ?></td>
								</tr>
							</tbody>
						</table>
						<p class="description"><?php _e( 'Note: the index is always kept as small as posisble.', 'searchwp' ); ?></p>
					</div>
				</div>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {

//					var $stats_meta_box = $('.searchwp-settings-stats'),
//						$actions_meta_boxes = $('.searchwp-common-actions');
//
//					if($stats_meta_box.outerHeight()<$actions_meta_boxes.outerHeight()){
//						$stats_meta_box.height($actions_meta_boxes.outerHeight()-$stats_meta_box.css('marginTop').replace('px','')-$stats_meta_box.css('marginBottom').replace('px','')-2);
//					}

					$('#swp-indexer-index_reset').click(function () {
						if (confirm('<?php echo esc_js( __( 'Are you SURE you want to delete the entire SearchWP index?', 'searchwp' ) ); ?>')) {
							return confirm('<?php echo esc_js( __( 'Are you completely sure? THIS CAN NOT BE UNDONE!', 'searchwp' ) ); ?>');
						}
						return false;
					});
					$('#swp-indexer-stats_reset').click(function () {
						if (confirm('<?php echo esc_js( __( 'Are you SURE you want to completely reset your Search Stats?', 'searchwp' ) ); ?>')) {
							return confirm('<?php echo esc_js( __( 'Are you completely sure? THIS CAN NOT BE UNDONE!', 'searchwp' ) ); ?>');
						}
						return false;
					});
					$('.searchwp-show-less-common-actions a').click(function(e){
						e.preventDefault();
						$('.searchwp-show-less-common-actions').hide();
						$('.searchwp-less-common-actions').show();
					});
				});
			</script>
		</div>
		<?php
		include dirname( __FILE__ ) . '/export-import.php';
	}

	/**
	 * Returns whether an action name has fully passed the nonce check
	 *
	 * @param $action_name
	 *
	 * @return bool
	 */
	function is_valid_action_request( $action_name ) {
		return in_array( $action_name, $this->pending_actions );
	}

	/**
	 * Callback for Reset Index action
	 */
	function reset_index() {
		if ( ! $this->is_valid_action_request( 'index_reset' ) ) {
			return;
		}

		do_action( 'searchwp_log', 'Resetting the index' );
		SWP()->purge_index();
	}

	/**
	 * Callback for Reset Stats action
	 */
	function reset_stats() {
		if ( ! $this->is_valid_action_request( 'stats_reset' ) ) {
			return;
		}

		do_action( 'searchwp_log', 'Resetting stats' );
		SWP()->reset_stats();
	}

	/**
	 * Callback for Wake Indexer action
	 */
	function indexer_wake() {
		if ( ! $this->is_valid_action_request( 'indexer_wake' ) ) {
			return;
		}

		do_action( 'searchwp_log', 'Waking up the indexer' );
		searchwp_wake_up_indexer();
		SWP()->trigger_index();
	}

	/**
	 * Callback for Toggle Indexer action
	 */
	function indexer_toggle() {
		if ( ! $this->is_valid_action_request( 'indexer_toggle' ) ) {
			return;
		}

		$paused = searchwp_get_option( 'paused' );
		$paused = empty( $paused ) ? false : true;

		// we have to output custom messaging here because these functions fire too late to reflect a proper status
		if ( $paused ) {
			SWP()->indexer_unpause();
			?><style type="text/css">.swp-notices .updated { display:none !important; }</style><?php
		} else {
			SWP()->indexer_pause();
			?><div class="updated notice"><p><?php echo wp_kses_post( __( 'The SearchWP indexer is currently <strong>disabled</strong>', 'searchwp' ) ); ?></p></div><?php
		}
	}

	/**
	 * Callback for Toggle Indexer action
	 */
	function toggle_nuke_on_delete() {
		if ( ! $this->is_valid_action_request( 'toggle_nuke_on_delete' ) ) {
			return;
		}

		$nuke_on_delete = searchwp_get_setting( 'nuke_on_delete' );
		$nuke_on_delete = empty( $nuke_on_delete ) ? false : true;

		// we have to output custom messaging here because these functions fire too late to reflect a proper status
		if ( $nuke_on_delete ) {
			searchwp_set_setting( 'nuke_on_delete', false );
			?><?php
		} else {
			searchwp_set_setting( 'nuke_on_delete', true );
			?><div class="updated notice"><p><?php _e( 'Nuke on Delete <strong>enabled</strong>', 'searchwp' ); ?></p></div><?php
		}
	}

	/**
	 * Callback if user chose to restore conflict notices
	 */
	function conflict_notices_reset() {
		if ( ! $this->is_valid_action_request( 'conflict_notices_reset' ) ) {
			return;
		}

		$existing_dismissals = searchwp_get_setting( 'dismissed' );
		$existing_dismissals['filter_conflicts'] = array();
		searchwp_set_setting( 'dismissed', $existing_dismissals );
	}

	/**
	 * Callback if user chose to recreate custom database tables
	 */
	function recreate_db_tables() {
		if ( ! $this->is_valid_action_request( 'recreate_db_tables' ) ) {
			return;
		}

		$upgrader = new SearchWPUpgrade();
		$upgrader->create_tables();

		SWP()->purge_index();

		$database_tables_recreated = SWP()->custom_db_tables_exist();

		if ( ! $database_tables_recreated ) {
			?>
			<div class="error notice">
				<p><?php echo __( 'There was an error recreating the database tables.', 'searchwp' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Callback if user chose to import settings
	 */
	function maybe_import_settings() {
		if ( isset( $_POST['searchwp_action'] )
		     && 'import_engine_config' === $_POST['searchwp_action']
		     && isset( $_REQUEST['_wpnonce'] )
		     && wp_verify_nonce( $_REQUEST['_wpnonce'], 'searchwp_import_engine_config' )
		     && isset( $_REQUEST['searchwp_import_source'] )
		) {
			$settings_to_import = stripslashes( $_REQUEST['searchwp_import_source'] );
			SWP()->import_settings( $settings_to_import );
			?>
			<div class="updated">
				<p><?php _e( 'Settings imported', 'searchwp' ); ?></p>
			</div>
		<?php
		}
	}
}

$searchwp_advanced_settings = new SearchWP_Settings_Implementation_Advanced();
$searchwp_advanced_settings->init();