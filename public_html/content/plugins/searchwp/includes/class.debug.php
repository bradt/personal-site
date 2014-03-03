<?php

global $wp_filesystem;

if( !defined( 'ABSPATH' ) ) die();

include_once ABSPATH . 'wp-admin/includes/file.php';

/**
 * Class SearchWPDebug is responsible for generating the search index
 */
class SearchWPDebug extends SearchWP {
	public $active;

	private $logfile;
	private $remoteMeta;

	private $apiPrefix = 'swpapi';

	function __construct() {
		global $wp_filesystem;

		// determine whether we are active
		$this->active = apply_filters( 'searchwp_debug', false );

		// if we're not active, don't do anything
		if( $this->active ) {
			$this->logfile = trailingslashit( $this->instance()->dir ) . 'debug.log';

			// init environment
			if( !file_exists( $this->logfile ) ) {
				WP_Filesystem();
				if( !$wp_filesystem->put_contents( $this->logfile, '' ) ); {
					$this->active = false;
				}
			}

			// after determining whether we can write to the logfile, add our action
			if( $this->active ) {
				add_action( 'searchwp_log', array( $this, 'log' ), 1, 2 );
			}
		}

		// handle remote debugging call
		if( isset( $_REQUEST[$this->apiPrefix . 'key'] ) && isset( $_REQUEST[$this->apiPrefix . 'action'] ) ) {
			$exitCode = -1;
			if( get_option( SEARCHWP_PREFIX . 'remote' ) == sanitize_text_field( $_REQUEST[$this->apiPrefix . 'key'] ) ) {
				$this->remoteMeta = get_option( SEARCHWP_PREFIX . 'remote_meta' );
				switch( $_REQUEST[$this->apiPrefix . 'action'] ) {
					case 'resetlicense':
						$this->resetLicenseStatus();
						break;
					case 'getenvironment':
						$this->getEnvironment();
						break;
					case 'getinstance':
						$this->getInstance();
						break;
					case 'getoutstandingattempts':
						$this->getOutstandingAttempts();
						break;
					case 'getoutstandingterms':
						$this->getOutstandingTerms();
						break;
					case 'wakeup':
						$this->wakeUpIndexer();
						break;
					case 'log':
						$this->getRecentLogEntries();
						break;
					default:
						$exitCode = 0;
						break;
				}
			} else {
				echo $exitCode;
			}
			die();
		}
	}

	function log( $message = '', $type = 'notice' ) {
		global $wp_filesystem;
		WP_Filesystem();

		// if we're not active, don't do anything
		if( !$this->active || !file_exists( $this->logfile ) ) {
			return false;
		}

		// get the existing log
		$existing = $wp_filesystem->get_contents( $this->logfile );

		// format our entry
		$entry = '[' . date( 'Y-d-m G:i:s', current_time( 'timestamp' ) ) . '][' . sanitize_text_field( $type ) . ']';

		// flag it with the process ID
		$entry .= '[' . parent::getPid() . ']';

		// sanitize the message
		$message = sanitize_text_field( esc_html( $message ) );
		$message = str_replace( '=&gt;', '=>', $message ); // put back array identifiers
		$message = str_replace( '&#039;', "'", $message ); // put back apostrophe's

		// finally append the message
		$entry .= ' ' . $message;

		// append the entry
		$log = $existing . "\n" . $entry;

		// write log
		$wp_filesystem->put_contents( $this->logfile, $log );
	}

	function resetLicenseStatus() {
		if( update_option( SEARCHWP_PREFIX . 'license_status', 'valid' ) ) {
			echo 'License status reset';
		} else {
			echo 'License already valid';
		}
	}

	function getEnvironment() {
		$environment = isset( $this->remoteMeta['environment'] ) ? $this->remoteMeta['environment'] : false;
		echo json_encode( $environment );
	}

	function getInstance() {
		echo json_encode( $this );
	}

	function wakeUpIndexer() {
		$running = get_option( SEARCHWP_PREFIX . 'running' );
		if( $running ) {
			echo 'Indexer thought it was running. ';
			delete_option( SEARCHWP_PREFIX . 'running' );
			delete_option( SEARCHWP_PREFIX . 'total' );
			delete_option( SEARCHWP_PREFIX . 'remaining' );
			delete_option( SEARCHWP_PREFIX . 'done' );
			delete_option( SEARCHWP_PREFIX . 'last_activity' );
		}
		$this->triggerIndex();
		echo 'Woken up.';
	}

	function getOutstandingAttempts() {
		$key = '_' . SEARCHWP_PREFIX . 'attempts';
		$args = array(
			'nopaging'          => true,
			'posts_per_page'    => -1,
			'fields'            => 'ids',
			'post_type'         => 'any',
			'suppress_filters'  => true,
			'meta_query'        => array(
				array(
					'key'       => $key,
					'compare'   => 'EXISTS'
				)
			)
		);
		$query = new WP_Query( $args );
		$posts = array_map( 'absint', $query->posts );

		$postMeta = array();
		foreach( $posts as $post_id ) {
			$postMeta[$post_id] = get_post_meta( $post_id, $key, true );
		}

		echo json_encode( $postMeta );
	}

	function getOutstandingTerms() {
		$key = '_' . SEARCHWP_PREFIX . 'terms';
		$args = array(
			'nopaging'          => true,
			'posts_per_page'    => -1,
			'fields'            => 'ids',
			'post_type'         => 'any',
			'suppress_filters'  => true,
			'meta_query'        => array(
				array(
					'key'       => $key,
					'compare'   => 'EXISTS'
				)
			)
		);
		$query = new WP_Query( $args );
		$posts = array_map( 'absint', $query->posts );

		$postMeta = array();
		foreach( $posts as $post_id ) {
			$postMeta[$post_id] = get_post_meta( $post_id, $key, true );
		}

		echo json_encode( $postMeta );
	}

	function getRecentLogEntries() {
		global $wpdb;

		$table = $wpdb->prefix . SEARCHWP_DBPREFIX . 'log';
		$results = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 50", 'OBJECT' );

		echo json_encode( $results );
	}
}

new SearchWPDebug();
