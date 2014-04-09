<?php

if( !defined( 'ABSPATH' ) ) die();

/**
 * Class SearchWPUpgrade handles any installation or upgrade procedures that need to take place
 *
 * @since 1.0
 */
class SearchWPUpgrade {
	/**
	 * @var string Active plugin version
	 * @since 1.0
	 */
	public $version;

	/**
	 * @var mixed|void The last version that was active
	 * @since 1.0
	 */
	public $last_version;


	/**
	 * Constructor
	 *
	 * @param $version string Plugin version being activated
	 * @since 1.0
	 */
	public function __construct( $version ) {
		$this->version      = $version;
		$this->last_version = get_option( SEARCHWP_PREFIX . 'version' );

		if( false == $this->last_version ) {
			$this->last_version = 0;
		}

		if( version_compare( $this->last_version, $this->version, '<' ) ) {
			if( version_compare( $this->last_version, '0.1.0', '<' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				$this->install();
			} else {
				$this->upgrade();
			}

			update_option( SEARCHWP_PREFIX . 'version', $this->version );
		}
	}


	/**
	 * Installation procedure. Save default settings and insert database tables.
	 *
	 * @since 1.0
	 */
	private function install() {
		global $wpdb;

		/**
		 * Save our default settings so we have a working search engine on activation
		 * that matches what WordPress does out of the box; include post types that are
		 * not specifically set to exclude_from_search
		 */
		$settings = array( 'engines' => array( 'default' => array(), ), );

		$post_types = array_merge(
			array(
				'post' => 'post',
				'page' => 'page'
			),
			get_post_types(
				array(
					'exclude_from_search' 	=> false,
					'_builtin' 				=> false
				)
			)
		);

		foreach( $post_types as $post_type ) {

			$settings['engines']['default'][$post_type] = array(
				'enabled'	=> true,
				'weights'	=> array()
			);

			$postTypeObject = get_post_type_object( $post_type );

			// set default title weight if applicable
			if( post_type_supports( $postTypeObject->name, 'title' ) ) {
				$settings['engines']['default'][$post_type]['weights']['title'] = searchwpGetEngineWeight( null, 'title' );
			}

			// set default content weight if applicable
			if( post_type_supports( $postTypeObject->name, 'editor' ) ) {
				$settings['engines']['default'][$post_type]['weights']['content'] = searchwpGetEngineWeight( null, 'content' );
			}

			// set default slug weight if applicable
			if( $postTypeObject->name == 'page' || $postTypeObject->publicly_queryable ) {
				$settings['engines']['default'][$post_type]['weights']['slug'] = searchwpGetEngineWeight( null, 'slug' );
			}

			// set default taxonomy weight(s) if applicable
			$taxonomies = get_object_taxonomies( $postTypeObject->name );
			if( is_array( $taxonomies ) && count( $taxonomies ) ) {
				$settings['engines']['default'][$post_type]['weights']['tax'] = array();
				foreach( $taxonomies as $taxonomy ) {
					if( $taxonomy != 'post_format' ) { // we don't want Post Formats here
						$settings['engines']['default'][$post_type]['weights']['tax'][$taxonomy] = searchwpGetEngineWeight( null, 'tax' );
					}
				}
			}

			// set default excerpt weight if applicable
			if( post_type_supports( $postTypeObject->name, 'excerpt' ) ) {
				$settings['engines']['default'][$post_type]['weights']['excerpt'] = searchwpGetEngineWeight( null, 'excerpt' );
			}

			// set default comment weight if applicable
			if( post_type_supports( $postTypeObject->name, 'comments' ) ) {
				$settings['engines']['default'][$post_type]['weights']['comment'] = searchwpGetEngineWeight( null, 'comment' );
			}

			// set our default options
			$settings['engines']['default'][$post_type]['options'] = array(
				'exclude'       => '',
				'attribute_to'  => '',
				'stem'          => '',
			);

		}

		searchwp_generate_settings( $settings['engines'] );


		/**
		 * Create our index tables
		 */

		// main index table
		$sql = "
			CREATE TABLE {$wpdb->prefix}swp_index (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				term bigint(20) unsigned NOT NULL,
				content bigint(20) unsigned NOT NULL DEFAULT '0',
				title bigint(20) unsigned NOT NULL DEFAULT '0',
				comment bigint(20) unsigned NOT NULL DEFAULT '0',
				excerpt bigint(20) unsigned NOT NULL DEFAULT '0',
				slug bigint(20) unsigned NOT NULL DEFAULT '0',
				post_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY (id),
				KEY termindex (term),
  				KEY postidindex (post_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

		// terms table
		$sql = "
			CREATE TABLE {$wpdb->prefix}swp_terms (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				term varchar(80) CHARACTER SET utf8 NOT NULL DEFAULT '',
				reverse varchar(80) CHARACTER SET utf8 NOT NULL DEFAULT '',
				stem varchar(80) CHARACTER SET utf8 NOT NULL DEFAULT '',
				PRIMARY KEY (id),
				UNIQUE KEY termunique (term),
				KEY termindex (term(2)),
  				KEY stemindex (stem(2))
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

		// custom field table
		$sql = "
			CREATE TABLE {$wpdb->prefix}swp_cf (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				metakey varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
				term int(20) unsigned NOT NULL,
				count bigint(20) unsigned NOT NULL,
				post_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY (id),
				KEY metakey (metakey),
				KEY term (term),
				KEY postidindex (post_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

		// taxonomy table
		$sql = "
			CREATE TABLE {$wpdb->prefix}swp_tax (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				taxonomy varchar(32) CHARACTER SET utf8 NOT NULL,
				term int(20) unsigned NOT NULL,
				count bigint(20) unsigned NOT NULL,
				post_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY (id),
				KEY taxonomy (taxonomy),
				KEY term (term),
				KEY postidindex (post_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

		// log table
		$sql = "
			CREATE TABLE {$wpdb->prefix}swp_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	            event enum('search','action') NOT NULL DEFAULT 'search',
	            query varchar(200) NOT NULL DEFAULT '',
	            tstamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	            hits mediumint(9) unsigned NOT NULL,
	            engine varchar(200) NOT NULL DEFAULT 'default',
	            wpsearch tinyint(1) NOT NULL,
	            PRIMARY KEY (id),
	            KEY eventindex (event),
	            KEY queryindex (query),
	            KEY engineindex (engine)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

	}


	/**
	 * Upgrade routines
	 *
	 * @since 1.0
	 */
	private function upgrade() {
		global $wpdb, $searchwp;

		if( version_compare( $this->last_version, '1.3.1', '<' ) ) {
			// clean up misuse of cron schedule
			wp_clear_scheduled_hook( 'swp_cron_indexer' );
		}

		if( version_compare( $this->last_version, '1.6.7', '<' ) ) {
			// truncate logs table
			$prefix = $wpdb->prefix . SEARCHWP_DBPREFIX;
			$tableName = $prefix . 'log';
			$wpdb->query( "TRUNCATE TABLE {$tableName}" );
		}

		if( version_compare( $this->last_version, '1.8', '<' ) ) {
			// fix a possible issue with settings storage resulting in MySQL errors after update
			$settings = get_option( SEARCHWP_PREFIX . 'settings' );
			if( is_array( $settings ) ) {
				// make sure additional array keys are present and defined
				foreach( $settings['engines'] as $engine_key => $engine_setting ) {
					foreach( $settings['engines'][$engine_key] as $post_type => $post_type_settings ) {
						if( is_array( $settings['engines'][$engine_key][$post_type] ) && ! is_array( $settings['engines'][$engine_key][$post_type]['options'] ) ) {
							$settings['engines'][$engine_key][$post_type]['options'] = array(
								'exclude' 		=> false,
								'attribute_to' 	=> false,
								'stem' 			=> false,
							);
						}
					}
				}
			}
			searchwp_update_option( 'settings', $settings );
		}

		// index cleanup and optimization
		if( version_compare( $this->last_version, '1.9', '<' ) ) {

			$index_exists = $wpdb->get_results( "SHOW INDEX FROM `{$wpdb->prefix}swp_terms` WHERE Key_name = 'termindex'" , ARRAY_N );
			if( ! empty( $index_exists ) ) {
				$wpdb->query("ALTER TABLE {$wpdb->prefix}swp_terms DROP INDEX termindex;");
			}

			$index_exists = $wpdb->get_results( "SHOW INDEX FROM `{$wpdb->prefix}swp_terms` WHERE Key_name = 'stemindex'" , ARRAY_N );
			if( ! empty( $index_exists ) ) {
				$wpdb->query("ALTER TABLE {$wpdb->prefix}swp_terms DROP INDEX stemindex;");
			}

			$index_exists = $wpdb->get_results( "SHOW INDEX FROM `{$wpdb->prefix}swp_terms` WHERE Key_name = 'id'" , ARRAY_N );
			if( ! empty( $index_exists ) ) {
				$wpdb->query("ALTER TABLE {$wpdb->prefix}swp_terms DROP INDEX id;");
			}

			$index_exists = $wpdb->get_results( "SHOW INDEX FROM `{$wpdb->prefix}swp_index` WHERE Key_name = 'id'" , ARRAY_N );
			if( ! empty( $index_exists ) ) {
				$wpdb->query("ALTER TABLE {$wpdb->prefix}swp_index DROP INDEX id;");
			}

			$wpdb->query("CREATE INDEX termindex ON {$wpdb->prefix}swp_terms(term(2));");
			$wpdb->query("CREATE INDEX stemindex ON {$wpdb->prefix}swp_terms(stem(2));");
		}

		// consolidate settings into one database record
		if( version_compare( $this->last_version, '1.9.1', '<' ) ) {

			$index_exists = $wpdb->get_results( "SHOW INDEX FROM `{$wpdb->prefix}swp_terms` WHERE Key_name = 'termindex'" , ARRAY_N );
			if( empty( $index_exists ) ) {
				$wpdb->query("CREATE INDEX termindex ON {$wpdb->prefix}swp_terms(term(2));");
			}

			$index_exists = $wpdb->get_results( "SHOW INDEX FROM `{$wpdb->prefix}swp_terms` WHERE Key_name = 'stemindex'" , ARRAY_N );
			if( empty( $index_exists ) ) {
				$wpdb->query("CREATE INDEX stemindex ON {$wpdb->prefix}swp_terms(term(2));");
			}

			$old_settings = searchwp_get_option( 'settings' );
			$engines = isset( $old_settings['engines'] ) ? $old_settings['engines'] : array();

			// clear out the old settings because we're using the same key
			searchwp_delete_option( 'settings' );

			// in with the new
			searchwp_generate_settings( $engines );

			// delete the old options
			searchwp_delete_option( 'activated' );
			searchwp_delete_option( 'license_nag' );
			searchwp_delete_option( 'dismissed' );
			searchwp_delete_option( 'ignored_queries' );
			searchwp_delete_option( 'indexer_nag' );
			searchwp_delete_option( 'valid_db_environment' );
			searchwp_delete_option( 'running' );
			searchwp_delete_option( 'total' );
			searchwp_delete_option( 'remaining' );
			searchwp_delete_option( 'done' );
			searchwp_delete_option( 'in_process' );
			searchwp_delete_option( 'initial' );
			searchwp_delete_option( 'initial_notified' );
			searchwp_delete_option( 'purgeQueue' );
			searchwp_delete_option( 'processingPurgeQueue' );
			searchwp_delete_option( 'mysql_version_nag' );
			searchwp_delete_option( 'remote' );
			searchwp_delete_option( 'remote_meta' );
			searchwp_delete_option( 'paused' );
			searchwp_delete_option( 'nuke_on_delete' );
			searchwp_delete_option( 'indexnonce' );
		}

		if( version_compare( $this->last_version, '1.9.2.2', '<' ) ) {
			searchwp_add_option( 'progress', -1 );
		}

		if( version_compare( $this->last_version, '1.9.4', '<' ) ) {
			// clean up a potential useless settings save
			$live_settings = searchwp_get_option( 'settings' );
			$update_settings_record = false;
			if( is_array( $live_settings ) ) {
				foreach( $live_settings as $live_setting_key => $live_setting_value ) {
					// none of our keys should be numeric (specifically going after a rogue 'running' setting that
					// may have been inadvertently set in 1.9.2, we just don't want it in there at all
					if( is_numeric( $live_setting_key ) ) {
						unset( $live_settings[$live_setting_key] );
						$update_settings_record = true;
					}
					// also update 'nuke_on_delete' to be a boolean if necessary
					if( 'nuke_on_delete' === $live_setting_key ) {
						$live_settings['nuke_on_delete'] = empty( $live_setting_value ) ? false : true;
						$update_settings_record = true;
					}
				}
			}
			if( $update_settings_record ) {
				// save the cleaned up settings array
				searchwp_update_option( 'settings', $live_settings );
				$searchwp->settings = $live_settings;
			}
		}

		if( version_compare( $this->last_version, '1.9.5', '<' ) ) {
			// move indexer-specific settings to their own record as they're being constantly updated
			$live_settings = searchwp_get_option( 'settings' );
			$indexer_settings = array();

			// whether the initial index has been built
			if( isset( $live_settings['initial_index_built'] ) ) {
				$indexer_settings['initial_index_built'] = (bool) $live_settings['initial_index_built'];
				unset( $live_settings['initial_index_built'] );
			} else {
				$indexer_settings['initial_index_built'] = false;
			}

			// all of the stats
			if( isset( $live_settings['stats'] ) ) {
				$indexer_settings['stats'] = $live_settings['stats'];
				unset( $live_settings['stats'] );
			} else {
				$indexer_settings['stats'] = array();
			}

			// whether the indexer is running
			if( isset( $live_settings['running'] ) ) {
				$indexer_settings['running'] = (bool) $live_settings['running'];
				unset( $live_settings['running'] );
			} else {
				$indexer_settings['running'] = false;
			}

			// whether the indexer is paused (disabled)
			if( isset( $live_settings['paused'] ) ) {
				$indexer_settings['paused'] = (bool) $live_settings['paused'];
				unset( $live_settings['paused'] );
			} else {
				$indexer_settings['paused'] = false;
			}

			// whether the indexer is processing the purge queue
			if( isset( $live_settings['processing_purge_queue'] ) ) {
				$indexer_settings['processing_purge_queue'] = (bool) $live_settings['processing_purge_queue'];
				unset( $live_settings['processing_purge_queue'] );
			} else {
				$indexer_settings['processing_purge_queue'] = false;
			}

			// the purge queue will be moved to it's own option to avoid conflict
			if( isset( $live_settings['purge_queue'] ) ) {
				searchwp_add_option( 'purge_queue', $live_settings['purge_queue'] );
				unset( $live_settings['purge_queue'] );
			}

			searchwp_update_option( 'settings', $live_settings );
			searchwp_add_option( 'indexer', $indexer_settings );

		}

		if( version_compare( $this->last_version, '1.9.6', '<' ) ) {
			// wake up the indexer if necessary
			$running = searchwp_get_setting( 'running' );
			if( empty( $running ) ) {
				searchwp_set_setting( 'running', false );
			}
		}

		// make ignored queries for search stats per-user
		if( version_compare( $this->last_version, '2.0.2', '<' ) ) {
			$user_id = get_current_user_id();
			if( $user_id ) {
				$ignored_queries = searchwp_get_setting( 'ignored_queries' );
				update_user_meta( $user_id, SEARCHWP_PREFIX . 'ignored_queries', $ignored_queries );
			}
		}

	}

}


function searchwp_generate_settings( $engines ) {
	global $searchwp;

	// grab this early because they're going to be nested
	$dismissed_filter_nags = searchwp_get_option( 'dismissed' );
	$dismissed_filter_nags = isset( $dismissed_filter_nags['filter_conflicts'] ) ? $dismissed_filter_nags['filter_conflicts'] : array();

	$in_process = searchwp_get_option( 'in_process' );
	$in_process = is_array( $in_process ) ? $in_process : null;

	// reformat all of the saved settings
	$new_settings = array(
		'engines' => $engines,
		'activated' => (bool) searchwp_get_option( 'activated' ),
		'dismissed' => array(
			'filter_conflicts' => $dismissed_filter_nags,
			'nags' => array(),
		),
		'notices' => array(),
		'valid_db_environment' => (bool) searchwp_get_option( 'valid_db_environment' ),
		'ignored_queries' => searchwp_get_option( 'ignored_queries' ),
		'remote' => searchwp_get_option( 'remote' ),
		'remote_meta' => searchwp_get_option( 'remote_meta' ),
		'nuke_on_delete' => searchwp_get_option( 'nuke_on_delete' ),
	);

	// break out settings specific to the indexer since that runs independently
	$indexer_settings = array(
		'initial_index_built' => (bool) searchwp_get_option( 'initial' ),
		'stats'     => array(
			'done' => (int) searchwp_get_option( 'done' ),
			'in_process' => $in_process,
			'remaining' => (int) searchwp_get_option( 'remaining' ),
			'total' => (int) searchwp_get_option( 'total' ),
			'last_activity' => (int) searchwp_get_option( 'last_activity' ),
		),
		'running' => (bool) searchwp_get_option( 'running' ),
		'paused' => searchwp_get_option( 'paused' ),
		'processing_purge_queue' => searchwp_get_option( 'processingPurgeQueue' ),
	);

	// set the nags
	if( searchwp_get_option( 'indexer_nag' ) ) {
		$new_settings['dismissed']['nags'][] = 'indexer';
	}
	if( searchwp_get_option( 'license_nag' ) ) {
		$new_settings['dismissed']['nags'][] = 'license';
	}
	if( searchwp_get_option( 'mysql_version_nag' ) ) {
		$new_settings['dismissed']['nags'][] = 'mysql_version';
	}

	// set the notices
	if( searchwp_get_option( 'initial_notified' ) ) {
		$new_settings['notices'][] = 'initial';
	}

	// save the new options
	searchwp_add_option( 'settings', $new_settings );
	searchwp_add_option( 'indexer', $indexer_settings );
	searchwp_add_option( 'purge_queue', searchwp_get_option( 'purgeQueue' ) );

	// force our new settings in place
	$searchwp->settings = $new_settings;
	$searchwp->settings_updated = true;
}
