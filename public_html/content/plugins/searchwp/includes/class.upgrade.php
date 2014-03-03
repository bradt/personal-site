<?php

if( !defined( 'ABSPATH' ) ) die();

/**
 * Class SearchWPUpgrade handles any installation or upgrade procedures that need to take place
 *
 * @since 1.0
 */
class SearchWPUpgrade
{
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
				'exclude'       => false,
				'attribute_to'  => false,
				'stem'          => false,
			);

		}

		add_option( SEARCHWP_PREFIX . 'settings', $settings );


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
  				KEY id (id),
  				KEY postidindex (post_id)
				) DEFAULT CHARSET=utf8;";
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
				KEY termindex (term),
  				KEY stemindex (stem),
  				KEY id (id)
				) DEFAULT CHARSET=utf8;";
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
				) DEFAULT CHARSET=utf8;";
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
				) DEFAULT CHARSET=utf8;";
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
			) DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

	}


	/**
	 * Upgrade routines
	 *
	 * @since 1.0
	 */
	private function upgrade() {
		global $wpdb;

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
						if( !is_array( $settings['engines'][$engine_key][$post_type]['options'] ) ) {
							$settings['engines'][$engine_key][$post_type]['options'] = array(
								'exclude' 		=> false,
								'attribute_to' 	=> false,
								'stem' 			=> false,
							);
						}
					}
				}
			}
			update_option( SEARCHWP_PREFIX . 'settings', $settings );
		}
	}

}
