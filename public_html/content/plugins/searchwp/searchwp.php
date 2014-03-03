<?php
/*
Plugin Name: SearchWP
Plugin URI: https://searchwp.com/
Description: The best WordPress search you can find
Version: 1.8.4
Author: Jonathan Christopher
Author URI: https://searchwp.com/
Text Domain: searchwp

Copyright 2013-2014 Jonathan Christopher

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// minimum WordPress version requirement
$wp_version = get_bloginfo( 'version' );
if ( version_compare( $wp_version, '3.5', '<' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	deactivate_plugins( __FILE__ );
	wp_die( esc_attr( __( 'SearchWP requires WordPress 3.5 or higher. Please upgrade before activating this plugin.' ) ) );
}

define( 'SEARCHWP_VERSION', '1.8.4' );
define( 'SEARCHWP_PREFIX', 'searchwp_' );
define( 'SEARCHWP_DBPREFIX', 'swp_' );
define( 'EDD_SEARCHWP_STORE_URL', 'http://searchwp.com' );
define( 'EDD_SEARCHWP_ITEM_NAME', 'SearchWP' );

global $searchwp;

/**
 * Class SearchWP
 * @since 1.0
 */
class SearchWP {
	/**
	 * @var string process identifier
	 * @since 1.5.5
	 */
	private $pid;

	/**
	 * @var SearchWP The SearchWP singleton
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * @var string License key
	 */
	public $license;

	/**
	 * @var string License status
	 */
	public $status;

	/**
	 * @var string The plugin directory
	 * @since 1.0
	 */
	public $dir;

	/**
	 * @var string The plugin URL
	 * @since 1.0
	 */
	public $url;

	/**
	 * @var string The plugin version
	 * @since 1.0
	 */
	public $version;

	/**
	 * @var bool Whether a search is taking place right now
	 * @since 1.0
	 */
	public $active = false;

	/**
	 * @var bool Whether SearchWP performed a search on this pageload
	 * @since 1.6.4
	 */
	public $ran = false;


	/**
	 * @var array Stores diagnostic information for debugging
	 * @since 1.6.4
	 */
	public $diagnostics = array();

	/**
	 * @var bool Whether indexing is taking place right now
	 * @since 1.0.6
	 */
	public $indexing = false;

	/**
	 * @var bool Whether we're in WordPress' main query
	 * @since 1.0
	 */
	public $isMainQuery = false;

	/**
	 * @var string Plugin name
	 * @since 1.0
	 */
	public $pluginName = 'SearchWP';

	/**
	 * @var string Plugin textdomain, used in localization
	 * @since 1.0
	 */
	public $textDomain = 'searchwp';

	/**
	 * @var array Stores custom field keys
	 * @since 1.0
	 */
	public $keys;

	/**
	 * @var array Stores all SearchWP settings
	 * @since 1.0
	 */
	public $settings;

	/**
	 * @var array Stores registered post types
	 */
	public $postTypes = array();

	/**
	 * @var array Common words as specified by Ando Saabas in Sphider http://www.sphider.eu/
	 * @since 1.0
	 */
	public $common = array( "a", "able", "about", "above", "across", "after", "afterwards", "again", "against", "ago",
		"all", "almost", "alone", "along", "already", "also", "although", "always", "am", "among", "amongst", "amoungst",
		"amount", "an", "and", "another", "any", "anyhow", "anyone", "anything", "anyway", "anywhere", "are", "aren't",
		"around", "as", "at", "back", "be", "became", "because", "become", "becomes", "becoming", "been", "before",
		"beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom",
		"but", "by", "call", "can", "can't", "cannot", "cant", "co", "con", "could", "couldn't", "couldnt", "cry", "de",
		"dear", "describe", "detail", "did", "do", "does", "don't", "done", "dont", "down", "due", "during", "each",
		"eg", "eight", "either", "eleven", "else", "elsewhere", "empty", "enough", "etc", "etc.", "even", "ever", "every",
		"everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first",
		"five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give",
		"go", "got", "had", "has", "hasn't", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby",
		"herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "i", "i.e.", "ie",
		"if", "in", "inc", "inc.", "indeed", "interest", "into", "is", "isn't", "it", "it's", "its", "itself", "just",
		"keep", "last", "latter", "latterly", "least", "less", "let", "like", "likely", "ltd", "ltd.", "made", "many",
		"may", "maybe", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much",
		"must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "new", "news", "next", "nine", "no",
		"no-one", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "old",
		"on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out",
		"over", "own", "page", "part", "per", "perhaps", "please", "put", "rather", "re", "said", "same", "say", "says",
		"see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "she's", "shes", "should", "show",
		"side", "since", "sincere", "six", "sixty", "small", "so", "some", "somehow", "someone", "something", "sometime",
		"sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "thank", "that", "the", "their",
		"theirs", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein",
		"thereupon", "these", "they", "they're", "theyre", "thickv", "thin", "third", "this", "those", "though", "three",
		"through", "throughout", "thru", "thus", "time", "times", "tis", "to", "together", "too", "top", "toward",
		"towards", "true", "twas", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "use", "users",
		"version", "very", "via", "want", "wants", "was", "way", "we", "web", "well", "were", "what", "whatever",
		"when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever",
		"whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "wide", "will", "with",
		"within", "without", "would", "yes", "yet", "you", "your", "yours", "yourself", "yourselves" );

	/**
	 * @var array Stores valid weight types
	 */
	public $validTypes = array( 'content', 'title', 'comment', 'tax', 'excerpt', 'cf', 'slug' );

	/**
	 * @var array Stores valid search engine option keys
	 */
	public $validOptions = array( 'exclude', 'attribute_to', 'stem', 'parent', 'mimes' );

	/**
	 * @var int Number of posts found in a query
	 */
	public $foundPosts = 0;

	/**
	 * @var int Number of pages in paginated results
	 */
	public $maxNumPages = 0;

	/**
	 * @var array Stores a purge queue
	 * @since 1.0.7
	 */
	private $purgeQueue = array();

	/**
	 * @var array
	 * @since 1.1
	 */
	public $extensions = array();

	/**
	 * @var array Database tables utilized
	 * @since 1.2.3
	 */
	private $tables = array(
		array( 'table' => 'cf',     'exists' => false ),  // custom fields
		array( 'table' => 'index',  'exists' => false ),  // main index
		array( 'table' => 'log',    'exists' => false ),  // log
		array( 'table' => 'tax',    'exists' => false ),  // taxonomies
		array( 'table' => 'terms',  'exists' => false ),  // terms
	);

	/**
	 * @var bool Whether the database environment has been properly established
	 * @since 1.2.3
	 */
	private $validDatabaseEnvironment = true;

	/**
	 * @var bool Whether the indexer has been paused by the user
	 * @since 1.4
	 */
	private $paused = false;


	/**
	 * Singleton
	 *
	 * @return SearchWP
	 * @since 1.0
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SearchWP ) ) {

			// store background indexer request
			if( isset( $_REQUEST['swpnonce'] ) ) {
				update_option( SEARCHWP_PREFIX . 'indexnonce', sanitize_text_field( $_REQUEST['swpnonce'] ) );
			}

			self::$instance = new SearchWP;
			self::$instance->init();

			// we want to purge a post from the index when comments are manipulated
			// TODO: make this more elaborate to only update what's necessary
			add_action( 'comment_post',   array( self::$instance, 'purgePostViaComment' ) );
			add_action( 'edit_comment',   array( self::$instance, 'purgePostViaComment' ) );
			add_action( 'trash_comment',  array( self::$instance, 'purgePostViaComment' ) );
			add_action( 'delete_comment', array( self::$instance, 'purgePostViaComment' ) );

			add_action( 'delete_attachment', array( self::$instance, 'purgePostViaEdit' ), 999 );

			// purge a post from the index when a related term is deleted
			add_action( 'set_object_terms', array( self::$instance, 'purgePostViaTerm' ), 10, 6 );

			// process the purge queue once everything is said and done
			add_action( 'shutdown', array( self::$instance, 'setupPurgeQueue' ) );
		}
		return self::$instance;
	}


	/**
	 * SearchWP queues up post objects that must be purged, this function records them
	 *
	 * @since 1.3.1
	 */
	function setupPurgeQueue() {
		if( !empty( $this->purgeQueue ) ) {
			do_action( 'searchwp_log', 'setupPurgeQueue() ' . count( $this->purgeQueue ) );
			$existingPurgeQueue = get_option( SEARCHWP_PREFIX . 'purgeQueue' );
			if ( is_array( $existingPurgeQueue ) && !empty( $existingPurgeQueue ) ) {
				foreach ( $existingPurgeQueue as $postToPurge ) {
					if ( ! isset( $this->purgeQueue[$postToPurge] ) ) {
						$this->purgeQueue[$postToPurge] = $postToPurge;
					}
				}
			}
			update_option( SEARCHWP_PREFIX . 'purgeQueue', $this->purgeQueue );
		}
	}


	/**
	 * Callback to WordPress' shutdown action, used to ensure only a single SearchWP process was running
	 *
	 * @since 1.5.5
	 */
	function shutdown() {
		do_action( 'searchwp_log', ' ' );
		do_action( 'searchwp_log', '========== END ' . $this->pid . ' ==========' );
		do_action( 'searchwp_log', ' ' );
	}


	/**
	 * Getter for the pid
	 *
	 * @return string process ID
	 * @since 1.5.5
	 */
	function getPid() {
		return $this->pid;
	}


	/**
	 * Initialization routine. Sets version, directory, url, adds WordPress hooks, includes includes, triggers index
	 *
	 * @uses  get_post_types to determine which post types are in use
	 * @since 1.0
	 */
	function init() {

		$this->version  = SEARCHWP_VERSION;
		$this->dir      = dirname( __FILE__ );
		$this->url      = plugins_url( 'searchwp', $this->dir );
		$this->settings = get_option( SEARCHWP_PREFIX . 'settings' );
		$this->license  = get_option( SEARCHWP_PREFIX . 'license_key' );
		$this->status   = get_option( SEARCHWP_PREFIX . 'license_status' );
		$this->pid      = uniqid();

		// includes
		include_once( $this->dir . '/includes/class.debug.php' );
		include_once( $this->dir . '/includes/class.stemmer.php' );
		include_once( $this->dir . '/includes/class.indexer.php' );
		include_once( $this->dir . '/templates/tmpl.engine.config.php' );
		include_once( $this->dir . '/templates/tmpl.supplemental.config.php' );
		include_once( $this->dir . '/includes/class.search.php' );
		include_once( $this->dir . '/includes/class.upgrade.php' );

		if ( ! class_exists( 'PDF2Text' ) ) {
			include_once( $this->dir . '/includes/class.pdf2text.php' );
		}

		if ( ! class_exists( 'pdf_readstream' ) ) {
			include_once( $this->dir . '/includes/class.pdfreadstream.php' );
		}

		// hooks
		add_filter( 'block_local_requests',         '__return_false' );
		add_action( 'admin_menu',                   array( $this, 'adminMenu' ) );
		add_action( 'admin_init',                   array( $this, 'initSettings' ) );
		add_action( 'admin_init',                   array( $this, 'activateLicense' ) );
		add_action( 'admin_init',                   array( $this, 'deactivateLicenseCheck' ) );
		add_action( 'init',                         array( $this, 'textdomain' ) );
		add_action( 'admin_notices',                array( $this, 'activation' ) );
		add_action( 'admin_notices',                array( $this, 'adminNotices' ), 9999 );
		add_filter( 'cron_schedules',               array( $this, 'addCustomCronInterval' ) );
		add_action( 'swp_maintenance',              array( $this, 'doMaintenance' ) );
		add_action( 'admin_init',                   array( $this, 'scheduleMaintenance' ) );
		add_action( 'swp_indexer',                  array( $this, 'doCron' ) );
		add_action( 'admin_enqueue_scripts',        array( $this, 'assets' ) );
		add_action( 'wp_ajax_swp_progress',         array( $this, 'getIndexProgress' ) );
		add_action( 'wp_ajax_swp_conflict',         array( $this, 'dismiss_filter_conflict' ) );
		add_action( 'pre_get_posts',                array( $this, 'checkForMainQuery' ), 0 );
		add_filter( 'the_posts',                    array( $this, 'wpSearch' ), 0, 2 );
		add_action( 'posts_request',                array( $this, 'maybeCancelWpQuery' ) );
		add_action( 'add_meta_boxes',               array( $this, 'documentContentMetaBox' ) );
		add_action( 'edit_attachment',              array( $this, 'documentContentSave' ) );
		add_action( 'wp_before_admin_bar_render',   array( $this, 'adminBarMenu' ) );
		add_action( 'shutdown',                     array( $this, 'shutdown' ) );
		add_action( 'wp_footer',                    array( $this, 'maybeOutputDebug' ) );
		add_action( 'wp_loaded',                    array( $this, 'load' ) );

		add_filter( 'plugin_action_links_searchwp/searchwp.php',  array( $this, 'plugin_update_link' ) );

		// support WordPress Importer by auto-pausing during imports
		add_action( 'import_start',                 array( $this, 'indexerPause' ) );
		add_action( 'import_end',                   array( $this, 'indexerUnpause' ) );

		add_action( 'current_screen', 				array( $this, 'check_update_check' ) );
	}


	/**
	 * Perform various environment checks/initializations on wp_loaded
	 *
	 * @since 1.8
	 */
	function load() {
		do_action( 'searchwp_log', ' ' );
		do_action( 'searchwp_log', '========== INIT ' . $this->pid . ' ' . SEARCHWP_VERSION . ' ==========' );
		do_action( 'searchwp_log', ' ' );

		// check for upgrade
		new SearchWPUpgrade( $this->version );

		// ensure working database environment
		$this->checkDatabaseEnvironment();

		// update remote debugging info if applicable
		if( get_option( SEARCHWP_PREFIX . 'remote' ) ) {
			add_action( 'shutdown', array( $this, 'updateRemoteMeta' ) );
		}

		// set the registered post types
		$this->postTypes = array_merge(
			array(
				'post'       => 'post',
				'page'       => 'page',
				'attachment' => 'attachment'
			),
			get_post_types(
				array(
					'exclude_from_search' => false,
					'_builtin'            => false
				)
			)
		);

		// allow filtration of what SearchWP considers common words (i.e. ignores)
		$this->common = apply_filters( 'searchwp_common_words', $this->common );
		if( is_array( $this->common ) ) {
			do_action( 'searchwp_log', 'Common words: ' . implode( ' ', $this->common ) );
		}

		do_action( 'searchwp_log', 'Standard check to see if the indexer is paused' );
		$this->checkIfPaused();

		$this->set_index_update_triggers();

		// implement registered Extensions
		$this->primeExtensions();

		// handle index and/or purge requests
		$this->updateIndex();

		// reset short circuit check
		$this->indexing = false;
	}


	/**
	 * Implement necessary hooks for delta index updates
	 *
	 * @since 1.8
	 */
	function set_index_update_triggers() {
		// index update triggers
		if ( is_admin() && current_user_can( 'edit_posts' ) ) {
			do_action( 'searchwp_log', 'User can edit_posts, implementing delta hooks' );
			add_action( 'save_post', array( $this, 'purgePostViaEdit' ), 999 );
			add_action( 'add_attachment', array( $this, 'purgePostViaEdit' ), 999 );
			add_action( 'edit_attachment', array( $this, 'purgePostViaEdit' ), 999 );
		} elseif ( is_admin() ) {
			do_action( 'searchwp_log', 'User cannot edit_posts, delta hooks omitted' );
		}

		if ( is_admin() && current_user_can( 'delete_posts' ) ) {
			do_action( 'searchwp_log', 'User can delete_posts, implementing delta hooks' );
			add_action( 'before_delete_post', array( $this, 'purgePostViaEdit' ), 999 );
		} elseif ( is_admin() ) {
			do_action( 'searchwp_log', 'User cannot delete_posts, delta hooks omitted' );
		}
	}


	/**
	 * Add an Update force check on the plugin page
	 *
	 * @param $links
	 *
	 * @return array Links to include on the Plugins page
	 */
	function plugin_update_link( $links )
	{
		if ( current_user_can( 'update_plugins' ) ) {
			$nonce = wp_create_nonce( 'swpupdatecheck' );
			$links[] = '<a href="plugins.php?swpupdate=' . $nonce . '">'. __( 'Check for update', 'searchwp' ) . '</a>';
		}
		return $links;
	}


	/**
	 * Outputs HTML comments containing diagnostic information about what took place during a single pageload
	 *
	 * @since 1.6.4
	 */
	function maybeOutputDebug() {

if( apply_filters( 'searchwp_debug', false ) ) { ?>

<!-- [SearchWP] Debug Information

SearchWP performed a search: <?php echo $this->ran ? 'Yes' : 'No'; ?>

Searches performed: <?php echo count( $this->diagnostics ); ?>
<?php $searchCount = 1; foreach( $this->diagnostics as $diagnostics ) : ?>


== SEARCH <?php echo $searchCount; ?> ==
Search Engine: <?php echo ( isset( $diagnostics['engine'] ) ) ? $diagnostics['engine'] : '[[ERROR]]'; ?>

Accepted search terms: <?php echo ( is_array( $diagnostics['terms'] ) && ! empty( $diagnostics['terms'] ) ) ? implode( ' ', $diagnostics['terms'] ) : '[[NONE]]'; ?>

Total results found: <?php echo ( isset( $diagnostics['found_posts'] ) ) ? $diagnostics['found_posts'] : '[[ERROR]]'; ?>

Total query time: <?php echo ( isset( $diagnostics['profiler'] ) ) ? $diagnostics['profiler']['after'] - $diagnostics['profiler']['before'] : '[[ERROR]]'; ?>s
Results in this set:
<?php
	// grab just post IDs and titles
	$postsArePosts = true;
	if( is_array( $diagnostics['posts'] ) && isset( $diagnostics['posts'][0] ) ) {
		if( is_numeric( $diagnostics['posts'][0] ) ) {
			// developer wanted only post IDs
			$postsArePosts = false;
		}
		foreach( $diagnostics['posts'] as $key => $post ) {
			// get the proper ID and title
			if( $postsArePosts ) {
				$post_id = $post->ID;
				$post_title = $post->post_title;
			} else {
				$post_id = $post;
				$post_title = get_the_title( $post );
			}

			// update the array key with a streamlined value
			echo '[' . $post_id . '] ' . $post_title . "\n";
		}
	} else {
		echo '[[NONE]]';
	}
?>
<?php $searchCount++; endforeach; ?>

-->
		<?php }
	}


	/**
	 * Add the SearchWP Admin Bar root menu
	 *
	 * @since 1.5
	 */
	function adminBarAddRootMenu( $name, $id, $href = false ) {
		global $wp_admin_bar;
		if( !is_admin_bar_showing() ) {
			do_action( 'searchwp_log', 'Admin Bar not showing, do not add root menu' );
			return;
		} else {
			do_action( 'searchwp_log', 'Admin Bar is showing, proceed to add root menu' );
		}

		$wp_admin_bar->add_menu( array(
			'id'      => $id,
			'meta'    => array(),
			'title'   => $name,
			'href'    => $href )
		);
	}


	/**
	 * Add a SearchWP Admin Bar sub menu
	 *
	 * @since 1.5
	 */
	function adminBarAddSubMenu( $name, $link, $root_menu, $id, $meta = false ) {
		global $wp_admin_bar;
		if( ! is_admin_bar_showing() ) {
			do_action( 'searchwp_log', 'Admin Bar not showing, do not add sub menu' );
			return;
		} else {
			do_action( 'searchwp_log', 'Admin Bar is showing, proceed to add sub menu' );
		}

		$wp_admin_bar->add_menu( array(
			'parent'  => $root_menu,
			'id'      => $id,
			'title'   => $name,
			'href'    => $link,
			'meta'    => $meta )
		);
	}


	/**
	 * Determine the last time a post was indexed
	 *
	 * @since 1.5
	 */
	function getLastIndexedTime( $post_id, $timeDiff = false ) {
		global $wpdb;

		do_action( 'searchwp_log', 'getLastIndexedTime()' );

		if( empty( $post_id ) ) {
			do_action( 'searchwp_log', 'No $post_id provided' );
			return false;
		}

		$lastIndex = get_post_meta( $post_id, '_' . SEARCHWP_PREFIX . 'last_index', true );

		$timestamp = ( !empty( $lastIndex ) ) ? absint( $lastIndex ) : false;
		$timestamp = ( $timeDiff && $timestamp ) ? human_time_diff( date( 'U', $timestamp ), current_time( 'timestamp' ) ) . __( ' ago', 'searchwp' ) : $timestamp;

		do_action( 'searchwp_log', 'Timestamp: ' . $timestamp );

		return $timestamp;
	}


	/**
	 * Callback to implement the SearchWP Admin Bar menu item
	 *
	 * @since 1.5
	 */
	function adminBarMenu() {
		global $pagenow, $post, $wpdb;

		do_action( 'searchwp_log', 'adminBarMenu()' );

		if( !apply_filters( 'searchwp_admin_bar', true ) ) {
			do_action( 'searchwp_log', 'searchwp_admin_bar is false' );
			return;
		}

		// only show in the admin and if user can manage options
		if( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// only show if user can manage

		// root menu
		$this->adminBarAddRootMenu(
			'SearchWP',
			$this->textDomain,
			get_admin_url() . 'options-general.php?page=' . $this->textDomain
		);

		// pause toggle
		$toggleLabel = get_option( SEARCHWP_PREFIX . 'paused' ) ? __( 'Unpause Indexer (currently: paused)', 'searchwp' ) : __( 'Pause Indexer  (currently: running)', 'searchwp' );
		$this->adminBarAddSubMenu(
			$toggleLabel,
			add_query_arg( 'nonce', wp_create_nonce( 'swppausenonce' ) ),
			$this->textDomain,
			$this->textDomain . '_toggle_pause'
		);

		switch( $pagenow ) {
			case 'post.php':
				do_action( 'searchwp_log', 'Current page is post.php' );
				if( isset( $post->ID ) ) {
					do_action( 'searchwp_log', '$post->ID = ' . $post->ID );

					// we need to pull the purge queue manually to see if this post is currently waiting to be indexed
					$tmpPurgeQueue = get_option( SEARCHWP_PREFIX . 'purgeQueue' );
					do_action( 'searchwp_log', 'Temporary purge queue: ' . print_r( $tmpPurgeQueue, true ) );

					// if we happen to be viewing an edit screen for a post in line to be indexed, say so
					if( is_array( $tmpPurgeQueue ) && in_array( $post->ID, $tmpPurgeQueue ) ) {
						do_action( 'searchwp_log', 'Currently being indexed' );
						$lastIndexedMessage = __( 'Currently Being Indexed', 'searchwp' );
					} else {
						// last indexed
						$lastIndexed = $this->getLastIndexedTime( $post->ID, true );

						// there's a chance this functionality was added after a post actually was indexed, so let's check for that
						if ( ! $lastIndexed ) {
							// see if this post ID is in the index
							$postInIndex = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swp_index WHERE {$wpdb->prefix}swp_index.post_id = %d LIMIT 1", $post->ID ) );
							if( ! empty( $postInIndex ) ) {
								$lastIndexedMessage = __( 'This entry is indexed', 'searchwp' );
							} else {
								$lastIndexedMessage = __( 'Not indexed', 'searchwp' );
							}
						} else {
							$lastIndexedMessage = __( 'Last indexed ', 'searchwp' ) . $lastIndexed;
						}

						do_action( 'searchwp_log', $lastIndexedMessage );
					}

					// add the menu item
					$this->adminBarAddSubMenu(
						$lastIndexedMessage,
						null,
						$this->textDomain,
						$this->textDomain . '_last_indexed'
					);
				} else {
					do_action( 'searchwp_log', '$post->ID was not defined' );
				}
				break;
		}

	}


	/**
	 * Pause the indexer programmatically
	 *
	 * @since 1.5
	 */
	function indexerPause() {
		do_action( 'searchwp_log', 'indexerPause()' );
		update_option( SEARCHWP_PREFIX . 'paused', true );
		$this->paused = true;
	}


	/**
	 * Unpause the indexer programmatically
	 *
	 * @since 1.5
	 */
	function indexerUnpause() {
		do_action( 'searchwp_log', 'indexerUnpause()' );
		delete_option( SEARCHWP_PREFIX . 'paused' );
		$this->paused = false;
		$this->triggerIndex();
	}


	/**
	 * Called from the Advanced Settings page, toggles the global indexer pause flag
	 *
	 * @since 1.4
	 */
	function checkIfPaused() {

		do_action( 'searchwp_log', 'checkIfPaused()' );
		$this->paused = get_option( SEARCHWP_PREFIX . 'paused' );
		do_action( 'searchwp_log', var_export( $this->paused, true ) );

		if (
				( ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swpadvanced' ) ) &&
				( isset( $_REQUEST['action'] ) && wp_verify_nonce( $_REQUEST['action'], 'swppauseindexer' ) )
				&& current_user_can( 'manage_options' ) )
				||
				( ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swppausenonce' ) )
						&& current_user_can( 'manage_options' ) )
		) {
			if( $this->paused ) {
				$this->indexerUnpause();
			} else {
				$this->indexerPause();
			}
		}

		// allow devs to pause the indexer in realtime
		$this->paused = apply_filters( 'searchwp_indexer_paused', $this->paused );
		do_action( 'searchwp_log', 'searchwp_indexer_paused ' . var_export( $this->paused, true ) );
	}


	/**
	 * Output a notice whenever the indexer has been paused
	 *
	 * @since 1.4
	 */
	function adminNotices() {
		global $wp_filesystem;

		// whether the JavaScript for these notices has been output
		$javascript_deployed = false;

		do_action( 'searchwp_log', 'adminNotices()' );

		// output a notification if there are potential query_posts or WP_Query conflicts in search.php
		$search_template = locate_template( 'search.php' ) ? locate_template( 'search.php' ) : locate_template( 'index.php' );
		if( $search_template ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			$potential_conflicts = array( 'new WP_Query', 'query_posts' );
			$search_template_content = $wp_filesystem->get_contents_array( $search_template );
			$line_numbers = array();
			while ( list( $key, $line ) = each( $search_template_content ) ) {
				foreach( $potential_conflicts as $potential_conflict ) {
					if( false !== strpos( $line, $potential_conflict ) ) {
						$line_numbers[$key + 1][] = $potential_conflict;
					}
				}
			}
			if( ! empty( $line_numbers ) ) {
				add_action( 'admin_footer', array( $this, 'filter_conflict_javascript' ) );
				$javascript_deployed = true;
				?>
					<div class="updated">
						<p><?php _e( 'SearchWP has detected a <strong>theme conflict</strong> with the active theme.', 'searchwp' ); ?> <a class="swp-conflict-toggle swp-theme-conflict-show" href="#searchwp-conflict-theme"><?php _e( 'More info &raquo;', 'searchwp' ); ?></a></p>
						<div id="searchwp-conflict-theme" style="background:#fafafa;border:1px solid #eaeaea;padding:0.6em 1.2em;border-radius:2px;margin-bottom:1em;display:none;">
							<p><?php _e( "In order for SearchWP to display it's results, occurrences of <code>new WP_Query</code> and <code>query_posts()</code> must be removed from your search results template.", 'searchwp' ); ?></p>
							<p>
								<strong><?php _e( 'File location', 'searchwp' ); ?>:</strong>
								<code><?php echo $search_template; ?></code>
							</p>
							<?php foreach( $line_numbers as $line_number => $conflicts ) : ?>
								<p>
									<strong><?php _e( 'Line', 'searchwp' ); ?>: <?php echo $line_number; ?></strong>
									<code><?php echo implode( '</code>, <code>', $conflicts ); ?></code>
								</p>
							<?php endforeach; ?>
							<p><?php _e( 'Please ensure the offending lines are removed from the theme template to avoid conflicts with SearchWP. When removed, this notice will disappear.', 'searchwp' ); ?></p>
						</div>
					</div>
				<?php
			}
		}

		// output a notification if there are potential action/filter conflicts
		if( is_array( $GLOBALS ) ) {
			if( isset( $GLOBALS['wp_filter'] ) ) {

				// whitelist which functions are acceptable
				$function_whitelist = array(
					'_close_comments_for_old_posts',    // WordPress core
					'SearchWP::wpSearch',               // SearchWP search hijack
					'SearchWP::checkForMainQuery',      // SearchWP main query check
				);

				// the filters we want to check for conflicts and their associated Knowledge Base resources
				$filter_checklist = array(
					'pre_get_posts'     => 'https://searchwp.com/?p=10370',
					'the_posts'         => 'https://searchwp.com/?p=10370',
				);

				foreach( $filter_checklist as $filter_name => $filter_resolution_url ) {
					if( isset( $GLOBALS['wp_filter'][$filter_name] ) ) {
						$potential_conflict = false;
						foreach( $GLOBALS['wp_filter'][$filter_name] as $filter_priority ) {
							foreach( $filter_priority as $filter_hook ) {
								if( isset( $filter_hook['function'] ) ) {

									// the function 'name' is either going to be just that (the function name) or
									// it's also going to include the class name for easier debugging
									// if it's a Closure we'll call that out too
									$function = $filter_hook['function'];
									if( is_object( $function ) && ( $function instanceof Closure ) ) {
										$function_name = 'Anonymous Function (Closure)';
									} elseif ( is_array( $function ) ) {
										if( is_object( $filter_hook['function'][0] ) ) {
											$function_name = get_class( $filter_hook['function'][0] ) . '::' . $filter_hook['function'][1];
										} else {
											$function_name = (string) $filter_hook['function'][0] . '::' . $filter_hook['function'][1];
										}
									} else {
										$function_name = $filter_hook['function'];
									}

									if( ! in_array( $function_name, $function_whitelist ) ) {
										// we're going to store all potential conflicts for the warning message
										if( !is_array( $potential_conflict ) ) {
											$potential_conflict = array();
										}
										$potential_conflict[] = $function_name;
									}
								}
							}
						}

						if( $potential_conflict ) {
							// user may have already dismissed this conflict so let's check
							$existing_dismissals = get_option( SEARCHWP_PREFIX . 'dismissed' );

							// dismissals are stored as hashes of the hooks as they were when the dismissal was enabled
							$conflict_hash = md5( json_encode( $potential_conflict ) );
							$conflict_nonce = wp_create_nonce( 'swpconflict_' . $filter_name );

							// by default we want to show it, but we'll check to see if it was already dismissed
							$show_conflict = true;
							if( is_array( $existing_dismissals ) ) {
								if( isset( $existing_dismissals['filter_conflicts'] ) && is_array( $existing_dismissals['filter_conflicts'] ) ) {
									if( in_array( $conflict_hash, $existing_dismissals['filter_conflicts'] ) ) {
										$show_conflict = false;
									}
								}
							}

							if( $show_conflict ) {
								// dump out the JavaScript that allows dismissals
								if( ! $javascript_deployed ) {
									add_action( 'admin_footer', array( $this, 'filter_conflict_javascript' ) );
									$javascript_deployed = true;
								}
								?>
								<div class="updated">
									<p><?php echo sprintf( __( 'SearchWP has detected a potential <strong>action/filter conflict</strong> with <code>%s</code> caused by an active plugin or the active theme.', 'searchwp' ), $filter_name ); ?> <a class="swp-conflict-toggle swp-filter-conflict-show" href="#searchwp-conflict-<?php echo $filter_name; ?>"><?php _e( 'More info &raquo;', 'searchwp' ); ?></a></p>
									<div id="searchwp-conflict-<?php echo $filter_name; ?>" style="background:#fafafa;border:1px solid #eaeaea;padding:0.6em 1.2em;border-radius:2px;margin-bottom:1em;display:none;">
										<p><?php _e( '<strong>If you are experiencing issues with search results not changing or not appearing</strong>, the following Hooks (put in place by other plugins or your active theme) are likely contributing to the problem:', 'searchwp' ); ?></p>
										<ol>
											<?php foreach( $potential_conflict as $conflict ) : ?>
												<?php
													// if it was class based we'll break out the class
													if( strpos( $conflict, '::' ) ) {
														$conflict = explode( '::', $conflict );
														$conflict = '<code>' . $conflict[1] . '</code> ' . __( '(method) in', 'searchwp' ) . ' <code>' . $conflict[0] . '</code>' . __( ' (class)', 'searchwp' );
													} else {
														$conflict = '<code>' . $conflict . '</code> ' . __( '(function)', 'searchwp' );
													}
												?>
												<li><?php echo $conflict; ?></li>
											<?php endforeach; ?>
										</ol>
										<p><?php echo sprintf( __( 'Using this information you can determine how to best disable this interference. For more information please see <a href="%s">this Knowledge Base article</a>.', 'searchwp' ), $filter_resolution_url ); ?></p>
										<p><a class="button swp-dismiss-conflict" href="#" data-hash="<?php echo esc_attr( $conflict_hash ); ?>" data-nonce="<?php echo esc_attr( $conflict_nonce ); ?>" data-filter="<?php echo esc_attr( $filter_name ); ?>"><?php _e( 'Dismiss this message', 'searchwp' ); ?></a></p>
									</div>
								</div>
							<?php
							}
						}
					}
				}
			}
		}

		// output a notification if the indexer is paused
		$paused = get_option( SEARCHWP_PREFIX . 'paused' );
		do_action( 'searchwp_log', '$paused = ' . var_export( $paused, true ) );
		if( $paused ) {
			?>
				<div class="updated">
					<p><?php _e( 'The SearchWP indexer is currently <strong>paused</strong>', 'searchwp' ); ?></p>
				</div>
			<?php
		}
	}


	/**
	 * If a filter conflict was detected, we need to set up our AJAX dismissal
	 *
	 * @since 1.8
	 */
	function filter_conflict_javascript() {
		?>
			<script type="text/javascript" >
				jQuery(document).ready(function($) {
					var data = { action: 'swp_conflict' };
					$('body').on('click','a.swp-dismiss-conflict',function(){
						data.swphash = $(this).data('hash');
						data.swpnonce = $(this).data('nonce');
						data.swpfilter = $(this).data('filter');
						$.post(ajaxurl, data, function(response) {});
						$(this).parents('.updated').remove();
						return false;
					});
					$('body').on('click','.swp-conflict-toggle',function(){
						var $target = $($(this).attr('href'));
						if($target.is(':visible')){
							$target.hide();
						}else{
							$target.show();
						}
						return false;
					});
				});
			</script>
		<?php
	}


	/**
	 * Callback for filter conflict notice dismissals
	 *
	 * @since 1.8
	 */
	function dismiss_filter_conflict() {
		// verify the request
		if ( isset( $_REQUEST['swphash'] ) && isset( $_REQUEST['swpnonce'] ) && isset( $_REQUEST['swpfilter'] ) ) {
			if( wp_verify_nonce( $_REQUEST['swpnonce'], 'swpconflict_' . $_REQUEST['swpfilter'] ) ) {

				// grab our existing dismissals and make sure our array key is set up
				$existing_dismissals = get_option( SEARCHWP_PREFIX . 'dismissed' );
				if( ! is_array( $existing_dismissals ) ) {
					$existing_dismissals = array();
				}
				if( ! isset( $existing_dismissals['filter_conflicts'] ) ) {
					$existing_dismissals['filter_conflicts'] = array();
				}

				// add this dismissal to the list and save it
				$existing_dismissals['filter_conflicts'][] = sanitize_text_field( $_REQUEST['swphash'] );
				$existing_dismissals['filter_conflicts'] = array_unique( $existing_dismissals['filter_conflicts'] );
				update_option( SEARCHWP_PREFIX . 'dismissed', $existing_dismissals );
			}
		}
		die();
	}


	/**
	 * Fire request to validate database environment and take proper action if requirements aren't met
	 *
	 * @since 1.3.1
	 */
	function checkDatabaseEnvironment() {
		global $wpdb;

		do_action( 'searchwp_log', 'checkDatabaseEnvironment()' );

		// make sure the database environment is proper
		if( false == get_option( SEARCHWP_PREFIX . 'valid_db_environment' ) ) {
			do_action( 'searchwp_log', 'Database environment unconfirmed' );
			$this->validateDatabaseEnvironment();
		} else {
			do_action( 'searchwp_log', 'Database environment is valid' );
		}

		if( is_admin() && !$this->validDatabaseEnvironment )
		{
			do_action( 'searchwp_log', 'Database environment invalid' );

			// automatically deactivate
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
			deactivate_plugins( __FILE__ );

			// determine which table(s) were not created
			$tables = array();
			foreach( $this->tables as $table )
				if( false === $table['exists'] ) $tables[] = $wpdb->prefix . SEARCHWP_DBPREFIX . $table['table'];

			$message = __( '<p>SearchWP <strong>has been automatically deactivated</strong> because it failed to create necessary database table(s):</p>', 'searchwp' );
			$message .= '<ul><li><code>' . implode( '</code></li><li><code>', $tables ) . '</code></li></ul>';
			$message .= __( '<p>Please ensure the applicable MySQL user has <code>CREATE</code> permissions and try activating again.</p>', 'searchwp' );
			$message .= '<p><a href="' . trailingslashit( get_admin_url() ) . 'plugins.php">' . __( 'Back to Plugins', 'searchwp' ) . '</a></p>';

			// output helpful message and die
			do_action( 'searchwp_log', 'Shutting down after discovering invalid database environment' );
			$this->shutdown();
			wp_die( $message );
		}
	}


	/**
	 * Perform initial Extension setup
	 *
	 * @since 1.3.1
	 */
	function primeExtensions() {
		do_action( 'searchwp_log', 'primeExtensions()' );

		// implement extensions
		$this->extensions = apply_filters( 'searchwp_extensions', array() );
		do_action( 'searchwp_log', '$this->extensions = ' . print_r( $this->extensions, true ) );

		if ( is_array( $this->extensions ) && ! empty( $this->extensions ) )
		{
			foreach ( $this->extensions as $extension => $path ) {
				$class_name = 'SearchWP' . $extension;

				if ( ! class_exists( $class_name ) && file_exists( $path ) ) {
					include_once( $path );
				}

				$this->extensions[$extension] = new $class_name( $this );

				// add plugin row action
				if ( isset( $this->extensions[$extension]->min_searchwp_version ) && version_compare( $this->version, $this->extensions[$extension]->min_searchwp_version, '<' ) ) {
					do_action( 'searchwp_log', 'after_plugin_row_' . plugin_basename( $path ) );
					add_action( 'after_plugin_row_' . plugin_basename( $path ), array( $this, 'pluginRow' ), 11, 3 );
				}
			}
		} else {
			do_action( 'searchwp_log', 'Extensions empty' );
		}
	}


	/**
	 * Potentially process background index/purge requests
	 *
	 * @since 1.3.1
	 */
	function updateIndex() {
		do_action( 'searchwp_log', 'updateIndex()' );

		// store the purge queue... just in case
		$toPurge = get_option( SEARCHWP_PREFIX . 'purgeQueue' );
		do_action( 'searchwp_log', '$toPurge = ' . var_export( $toPurge, true ) );

		// trigger background indexing
		if ( isset( $_REQUEST['swppurge'] ) && get_transient( 'swppurge' ) === sanitize_text_field( $_REQUEST['swppurge'] ) ) {
			if( is_array( $toPurge ) && !empty( $toPurge ) ) {
				do_action( 'searchwp_log', 'Purge queue (' . count( $toPurge ) . '): ' . implode( ', ', $toPurge ) );
				foreach ( $toPurge as $object_id ) {
					do_action( 'searchwp_log', 'Purge post ' . $object_id );
					$this->purgePost( intval( $object_id ) );
				}
			} else {
				do_action( 'searchwp_log', '$toPurge is inapplicable' );
			}

			delete_option( SEARCHWP_PREFIX . 'purgeQueue', array() );
			$this->purgeQueue = array();
			do_action( 'searchwp_log', 'Purge queue processed, triggerIndex()' );

			// allow developers the ability to disable automatic reindexing after edits in favor of their own method
			$automaticallyReindex = apply_filters( 'searchwp_auto_reindex', true );
			do_action( 'searchwp_log', '$automaticallyReindex = ' . print_r( $automaticallyReindex, true ) );
			if( $automaticallyReindex ) {
				$this->triggerIndex();
			}

			do_action( 'searchwp_log', 'Shutting down after purge request' );
			$this->shutdown();
			die();
		} elseif ( ! $this->paused && ! $this->indexing && get_transient( 'searchwp' ) === sanitize_text_field( $indexnonce = get_option( SEARCHWP_PREFIX . 'indexnonce' ) ) ) {
			$this->indexing = true;
			$hash = sanitize_text_field( $indexnonce );
			delete_option( SEARCHWP_PREFIX . 'indexnonce' );
			do_action( 'searchwp_log', 'Performing background index ' . $hash );
			new SearchWPIndexer( $hash );
			do_action( 'searchwp_log', 'Shutting down after triggering background index' );
			$this->shutdown();
			die();
		}

		// check to see if we need to process a purgeQueue
		if( is_array( $toPurge ) && !empty( $toPurge ) && false == get_option( SEARCHWP_PREFIX . 'processingPurgeQueue' ) ) {
			if( apply_filters( 'searchwp_background_deltas', true ) ) {
				do_action( 'searchwp_log', 'Automatic delta index update' );
				$this->processUpdates();
			} else {
				do_action( 'searchwp_log', 'Background delta index update prevented' );
			}
		} else {
			if( get_option( SEARCHWP_PREFIX . 'processingPurgeQueue' ) ) {
				do_action( 'searchwp_log', 'Cleaning up after processing purge queue' );
				delete_option( SEARCHWP_PREFIX . 'processingPurgeQueue' );
			} else {
				do_action( 'searchwp_log', 'Nothing to do with purge queue, backing out' );
				$this->shutdown();
			}
		}

	}


	/**
	 * Perform the delta index updates based on what's changed (the purge queue)
	 *
	 * @since 1.6
	 */
	function processUpdates() {
		do_action( 'searchwp_log', 'processUpdates()' );

		$toPurge = get_option( SEARCHWP_PREFIX . 'purgeQueue' );
		do_action( 'searchwp_log', '$toPurge = ' . var_export( $toPurge, true ) );

		if( is_array( $toPurge ) && !empty( $toPurge ) && false == get_option( SEARCHWP_PREFIX . 'processingPurgeQueue' ) ) {
			$hash = sha1( uniqid( 'searchwppurge' ) );
			set_transient( 'swppurge', $hash );

			update_option( SEARCHWP_PREFIX . 'processingPurgeQueue', true );
			do_action( 'searchwp_log', 'Deferred purge ' . trailingslashit( site_url() ) . '?swppurge=' . $hash );

			// fire off our background request
			$timeout = abs( apply_filters( 'searchwp_timeout', 0.02 ) );
			wp_remote_post(
				trailingslashit( site_url() ),
				array(
					'body'        => array( 'swppurge' => $hash ),
					'blocking'    => false,
					'user-agent'  => 'SearchWP',
					'timeout'     => $timeout,
					'sslverify'   => false
				)
			);
		}
	}


	/**
	 * Checks to make sure the proper database tables exist
	 *
	 * @since 1.2.3
	 */
	function validateDatabaseEnvironment() {
		global $wpdb;

		do_action( 'searchwp_log', 'validateDatabaseEnvironment()' );

		foreach( $this->tables as $tableKey => $tableMeta )
		{
			$tableSQL = $wpdb->get_results( "SHOW TABLES LIKE '" . $wpdb->prefix . SEARCHWP_DBPREFIX . $tableMeta['table'] . "'" , ARRAY_N );
			if( !empty( $tableSQL ) ) {
				$this->tables[$tableKey]['exists'] = true;
			} else {
				$this->validDatabaseEnvironment = false;
			}
		}

		update_option( SEARCHWP_PREFIX . 'valid_db_environment', $this->validDatabaseEnvironment );

	}


	/**
	 * Outputs an upgrade notice on the Plugins page
	 *
	 * @param $plugin_file
	 * @param $plugin_data
	 * @param $status
	 *
	 * @since 1.0
	 */
	function pluginRow( $plugin_file, $plugin_data, $status ) {
		do_action( 'searchwp_log', 'pluginRow()' );
		?>
		<tr class="plugin-update-tr searchwp">
			<td colspan="3" class="plugin-update">
				<div class="update-message">
					<?php _e( "SearchWP must be updated to the latest version to work with ", 'searchwp' ); ?> <?php echo $plugin_data['Name']; ?>
				</div>
			</td>
		</tr>
	<?php
	}


	/**
	 * Set up and trigger background index call
	 *
	 * @return array
	 */
	function triggerIndex() {
		$hash = sha1( uniqid( 'searchwpindex' ) );
		set_transient( 'searchwp', $hash );

		do_action( 'searchwp_log', 'triggerIndex() ' . trailingslashit( site_url() ) . '?swpnonce=' . $hash );

		$timeout = abs( apply_filters( 'searchwp_timeout', 0.02 ) );
		wp_remote_post(
			trailingslashit( site_url() ),
			array(
				'body'        => array( 'swpnonce' => $hash ),
				'blocking'    => false,
				'user-agent'  => 'SearchWP',
				'timeout'     => $timeout,
				'sslverify'   => false
			)
		);
	}


	/**
	 * Checks to see if we're in the main query and stores result as isMainQuery property
	 *
	 * @param WP_Query $query Instance of WP_Query to check
	 * @return mixed $query
	 *
	 * @since 1.0
	 */
	function checkForMainQuery( $query ) {
		do_action( 'searchwp_log', 'checkForMainQuery()' );
		if ( ! is_admin() && $query->is_main_query() ) {
			do_action( 'searchwp_log', 'It is the main query' );
			$this->isMainQuery = true;

			// plugin compat
			if( $query->is_search() ) {
				do_action( 'searchwp_log', 'It is a search' );
				remove_filter( 'pre_get_posts', 'CPTO_pre_get_posts' );   // Post Types Order
			} else {
				do_action( 'searchwp_log', 'It is not a search' );
			}
		} else {
			do_action( 'searchwp_log', 'It is not the main query' );
		}

		return $query;
	}


	/**
	 * Perform a search query
	 *
	 * @param string $engine The search engine name to use when performing the search
	 * @param        $terms  string|array The search terms to include in the query
	 * @param int    $page   Results are paged, return this page (1 based)
	 *
	 * @return array Search results post IDs ordered by weight DESC
	 * @uses  SearchWPSearch
	 * @since 1.0
	 */
	function search( $engine = 'default', $terms, $page = 1 ) {
		global $wpdb;

		do_action( 'searchwp_log', 'search()' );

		$this->active = true;
		$this->ran = true;

		// at the very least, our terms are the search query
		$terms = $originalQuery = is_array( $terms ) ? trim( implode( ' ', $terms ) ) : trim( (string) $terms );

		// this filter is also applied in the SearchWPSearch class constructor
		// TODO: should this be applied in both places? which?
		$sanitizeTerms = apply_filters( 'searchwp_sanitize_terms', true, $engine );
		if ( ! is_bool( $sanitizeTerms ) ) {
			$sanitizeTerms = true;
		}

		do_action( 'searchwp_log', '$sanitizeTerms = ' . print_r( $sanitizeTerms, true ) );

		// facilitate filtering the actual terms
		$terms = apply_filters( 'searchwp_terms', $terms, $engine );

		do_action( 'searchwp_log', '$terms = ' . print_r( $terms, true ) );

		// if we should still sanitize our terms, do it
		if ( $sanitizeTerms ) {
			$terms = $this->sanitizeTerms( $terms );
		} else {
			do_action( 'searchwp_log', 'Opted out of internal sanitization' );
		}

		// set up our engine name
		$engine = $this->isValidEngine( $engine ) ? $engine : '';

		do_action( 'searchwp_log', '$engine = ' . $engine );

		// make sure the search isn't overflowing with terms
		$maxSearchTerms = intval( apply_filters( 'searchwp_max_search_terms', 6, $engine ) );
		do_action( 'searchwp_log', 'searchwp_max_search_terms $maxSearchTerms = ' . $maxSearchTerms );
		$maxSearchTerms = intval( apply_filters( 'searchwp_max_search_terms_supplemental', $maxSearchTerms, $engine ) );
		do_action( 'searchwp_log', 'searchwp_max_search_terms_supplemental $maxSearchTerms = ' . $maxSearchTerms );
		$maxSearchTerms = intval( apply_filters( "searchwp_max_search_terms_{$engine}", $maxSearchTerms ) );
		do_action( 'searchwp_log', 'searchwp_max_search_terms_{$engine} $maxSearchTerms = ' . $maxSearchTerms );

		if ( count( $terms ) > $maxSearchTerms ) {
			$terms = array_slice( $terms, 0, $maxSearchTerms );
			do_action( 'searchwp_log', '$terms = ' . print_r( $terms, true ) );
		} else {
			do_action( 'searchwp_log', 'Terms within max search terms' );
		}

		// prep our args
		$args = array(
			'engine'         => $engine,
			'terms'          => $terms,
			'page'           => intval( $page ),
			'posts_per_page' => apply_filters( 'searchwp_posts_per_page', intval( get_option( 'posts_per_page' ) ), $engine, $terms, $page )
		);

		do_action( 'searchwp_log', '$args = ' . print_r( $args, true ) );

		// perform the search
		$profiler = array( 'before' => microtime() );
		$searchwp = new SearchWPSearch( $args );
		$profiler['after'] = microtime();

		$this->foundPosts = intval( $searchwp->foundPosts );
		$this->maxNumPages = intval( $searchwp->maxNumPages );

		// store diagnostics for debugging
		$this->diagnostics[] = array(
			'engine'        => $args['engine'],
			'terms'         => $args['terms'],
			'found_posts'   => $searchwp->foundPosts,
			'posts'         => $searchwp->posts,
			'profiler'      => $profiler,
			'args'          => $args
		);

		do_action( 'searchwp_log', '$this->foundPosts = ' . $this->foundPosts );
		do_action( 'searchwp_log', '$this->maxNumPages = ' . $this->maxNumPages );

		// log this
		$wpdb->insert(
			$wpdb->prefix . SEARCHWP_DBPREFIX . 'log',
			array(
				'event'    => 'search',
				'query'    => sanitize_text_field( $originalQuery ),
				'hits'     => $this->foundPosts,
				'engine'   => $engine,
				'wpsearch' => 0
			),
			array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%d'
			)
		);

		$this->active = false;

		$results = apply_filters( 'searchwp_results', $searchwp->posts, array(
			'terms'       => $terms,
			'page'        => $args['page'],
			'order'       => 'DESC',
			'foundPosts'  => $this->foundPosts,
			'maxNumPages' => $this->maxNumPages,
			'engine'      => $engine,
		) );

		return $results;
	}


	/**
	 * Determines if an engine name is considered valid (e.g. stored in the settings)
	 *
	 * @param $engineName string The engine name to check
	 *
	 * @return bool
	 */
	public function isValidEngine( $engineName ) {
		$engineName = sanitize_key( $engineName );
		$validEngine = is_string( $engineName ) && isset( $this->settings['engines'][$engineName] );
		do_action( 'searchwp_log', 'isValidEngine( ' . print_r( $engineName, true ) .  ' ) = ' . var_export( $validEngine, true ) );
		return $validEngine;
	}


	/**
	 * Removes punctuation
	 * @param $termString The dirty string
	 *
	 * @return string The cleaned string
	 */
	public function cleanTermString( $termString ) {
		$punctuation = array( "(", ")", "·", "'", "´", "’", "‘", "”", "“", "„", "—", "–", "×", "…", "€", "\n", ".", "," );

		if ( ! is_string( $termString ) ) {
			$termString = '';
		}

		$termString = sanitize_text_field( trim( $termString ) );
		$termString = strtolower( $termString );
		$termString = stripslashes( $termString );

		// remove punctuation
		$termString = str_replace( $punctuation, ' ', $termString );
		$termString = preg_replace( "/[[:punct:]]/uiU", " ", $termString );

		// remove spaces
		$termString = preg_replace( "/[[:space:]]/uiU", " ", $termString );
		$termString = trim( $termString );

		return $termString;
	}


	/**
	 * Sanitizes terms; should be trimmed, single words.
	 *
	 * @param $terms string|array The terms to sanitize
	 *
	 * @return array Valid terms
	 */
	public function sanitizeTerms( $terms ) {
		$validTerms = array();

		// always going to be a string when a search query is performed
		if ( is_string( $terms ) ) {
			// preprocess the string to strip out unwanted punctuation
			$terms = $this->cleanTermString( trim( $terms ) );

			$terms = ( strpos( $terms, ' ' ) !== false ) ? explode( ' ', $terms ) : array( $terms );
		}

		if ( is_array( $terms ) ) {
			foreach ( $terms as $key => $term ) {
				// prep the term
				$term = $this->cleanTermString( $term );

				if ( strpos( $term, ' ' ) ) {
					// append the new broken down terms
					$terms = array_merge( $terms, explode( ' ', $term ) );
				} else {
					// proceed
					$excludeCommon = apply_filters( 'searchwp_exclude_common', true );
					if ( ! is_bool( $excludeCommon ) ) {
						$excludeCommon = true;
					}
					if ( ( $excludeCommon && ! in_array( $term, $this->common ) ) || ! $excludeCommon ) {
						$minLength = absint( apply_filters( 'searchwp_minimum_word_length', 3 ) );
						if( $minLength <= strlen( $term ) ) {
							$validTerms[$key] = sanitize_text_field( trim( $term ) );
						}
					}
				}
			}
		}

		// after removing punctuation we might have some empty keys
		$validTerms = array_filter( $validTerms, 'strlen' );

		// we also might have duplicates
		$validTerms = array_values( array_unique( $validTerms ) );

		return $validTerms;
	}


	/**
	 * Prevent WordPress from performing it's own search database call
	 *
	 * @param $query
	 *
	 * @return bool|string
	 * @since 1.1.2
	 */
	function maybeCancelWpQuery( $query )
	{
		$proceedIfInAdmin = apply_filters( 'searchwp_in_admin', false );
		$overridden       = apply_filters( 'searchwp_force_wp_query', false );
		$shortCircuit     = apply_filters( 'searchwp_short_circuit', false, $this );

		if( ! $shortCircuit && ! $overridden && ! ( is_admin() && ! $proceedIfInAdmin ) && !is_feed() && is_search() && $this->isMainQuery ) {
			$query = false;
			do_action( 'searchwp_log', 'maybeCancelWpQuery() canceled the query ' );
		}

		return $query;
	}


	/**
	 * Callback for the_posts filter. Hijacks WordPress searches and returns SearchWP results
	 *
	 * @param $posts array The original posts array from WordPress' query
	 *
	 * @return array The posts in the search results from SearchWP
	 * @uses  SearchWPSearch
	 * @since 1.0
	 */
	function wpSearch( $posts ) {
		global $wp_query, $wpdb;

		do_action( 'searchwp_log', 'wpSearch()' );

		// make sure we're not in the admin, that we are searching, that it is the main query, and that SearchWP is not active
		$proceedIfInAdmin = apply_filters( 'searchwp_in_admin', false );
		if ( is_admin() && ! $proceedIfInAdmin ) {
			do_action( 'searchwp_log', 'Not applicable because is_admin() and !$proceedIfInAdmin' );
			return $posts;
		}

		// allow developers to NOT use SearchWP if another plugin is using $_GET['s'] for specific functionality
		if( apply_filters( 'searchwp_short_circuit', false, $this ) ) {
			do_action( 'searchwp_log', 'Short circuiting at this time' );
			return $posts;
		}

		// make sure we do in fact want to proceed
		$force_search = apply_filters( 'searchwp_outside_main_query', false );
		if ( ! $wp_query->is_search || ( ! $this->isMainQuery && ! $force_search ) || $this->active ) {
			do_action( 'searchwp_log', 'Not applicable/active at this time' );
			return $posts;
		}

		// a search is currently taking place, let's provide some wicked better results
		$this->active = true;
		$this->ran = true;
		$wpPaged = ( intval( $wp_query->query_vars['paged'] ) > 0 ) ? intval( $wp_query->query_vars['paged'] ) : 1;
		do_action( 'searchwp_log', '$wpPaged = ' . $wpPaged );

		// at the very least, our terms are the search query
		$originalQuery = $wp_query->query_vars['s'];
		$terms = stripslashes( strtolower( trim( $wp_query->query_vars['s'] ) ) );
		do_action( 'searchwp_log', '$terms = ' . var_export( $terms, true ) );

		// facilitate filtering the actual terms
		$terms = apply_filters( 'searchwp_terms', $terms, 'default' );
		do_action( 'searchwp_log', '$terms after searchwp_terms = ' . var_export( $terms, true ) );

		// handle sanitization
		$sanitizeTerms = apply_filters( 'searchwp_sanitize_terms', true, 'default' );
		if ( ! is_bool( $sanitizeTerms ) ) {
			$sanitizeTerms = true;
		}

		// if we should still sanitize our terms, do it
		if ( $sanitizeTerms ) {
			$terms = $this->sanitizeTerms( $terms );
		}

		do_action( 'searchwp_log', '$terms after sanitization = ' . var_export( $terms, true ) );

		// determine the order from WP_Query
		$order = ( strtoupper( $wp_query->query_vars['order'] ) == 'DESC' ) ? 'DESC' : 'ASC';
		do_action( 'searchwp_log', '$order = ' . $order );

		// make sure the search isn't overflowing with terms
		$maxSearchTerms = intval( apply_filters( 'searchwp_max_search_terms', 6, 'default' ) );
		do_action( 'searchwp_log', '$maxSearchTerms = ' . $maxSearchTerms );

		if ( count( $terms ) > $maxSearchTerms ) {
			$terms = array_slice( $terms, 0, $maxSearchTerms );

			// need to tell $wp_query that we hijacked this
			$wp_query->query['s'] = $wp_query->query_vars['s'] = sanitize_text_field( implode( ' ', $terms ) );

			do_action( 'searchwp_log', 'Breached max terms count, $terms = ' . var_export( $terms, true ) );
		}

		if ( ! empty( $terms ) ) {
			$args = array(
				'terms'             => $terms,
				'page'              => $wpPaged,
				'order'             => $order,
				'posts_per_page'    => apply_filters( 'searchwp_posts_per_page', intval( get_option( 'posts_per_page' ) ), 'default', $terms, $wpPaged ),
			);

			// perform the search
			$profiler = array( 'before' => microtime() );
			$searchwp = new SearchWPSearch( $args );
			$profiler['after'] = microtime();

			$this->active = false;
			$this->isMainQuery = false;

			// we need to tell WP Query about everything that's different as per these better results
			$wp_query->found_posts = absint( $searchwp->foundPosts );
			$wp_query->max_num_pages = absint( $searchwp->maxNumPages );

			do_action( 'searchwp_log', 'found_posts = ' . $wp_query->found_posts );
			do_action( 'searchwp_log', 'max_num_pages = ' . $wp_query->max_num_pages );

			// store diagnostics for debugging
			$this->diagnostics[] = array(
				'engine'        => 'default',
				'terms'         => $args['terms'],
				'found_posts'   => $searchwp->foundPosts,
				'posts'         => $searchwp->posts,
				'profiler'      => $profiler,
				'args'          => $args
			);

			// log this search
			$wpdb->insert(
				$wpdb->prefix . SEARCHWP_DBPREFIX . 'log',
				array(
					'event'    => 'search',
					'query'    => sanitize_text_field( $originalQuery ),
					'hits'     => $wp_query->found_posts,
					'wpsearch' => 1
				),
				array(
					'%s',
					'%s',
					'%d',
					'%d'
				)
			);

			$results = apply_filters( 'searchwp_results', $searchwp->posts, array(
				'terms'       => $terms,
				'page'        => $wpPaged,
				'order'       => $order,
				'foundPosts'  => $wp_query->found_posts,
				'maxNumPages' => $wp_query->max_num_pages,
				'engine'      => 'default',
			) );

			return $results;
		}
		else {
			return $posts;
		}
	}


	/**
	 * Callback for admin_menu action; adds SearchWP link to Settings menu in the WordPress admin
	 *
	 * @since 1.0
	 */
	function adminMenu() {
		add_options_page( $this->pluginName, __( $this->pluginName, 'searchwp' ), 'manage_options', $this->textDomain, array( $this, 'optionsPage' ) );
		add_dashboard_page( __( 'Search Statistics', 'searchwp' ), __( 'Search Stats', 'searchwp' ), 'publish_posts', $this->textDomain . '-stats', array( $this, 'statsPage' ) );
	}


	/**
	 * Callback for admin_enqueue_scripts. Enqueues our assets.
	 *
	 * @param $hook string
	 *
	 * @since 1.0
	 */
	function assets( $hook ) {
		$baseURL = trailingslashit( $this->url );
		wp_register_style( 'select2',                 $baseURL . 'assets/vendor/select2/select2.css', null, '3.4.1', 'screen' );
		wp_register_style( 'swp_admin_css',           $baseURL . 'assets/css/searchwp.css', false, $this->version );
		wp_register_style( 'swp_stats_css',           $baseURL . 'assets/css/searchwp-stats.css', false, $this->version );

		wp_register_script( 'select2',                $baseURL . 'assets/vendor/select2/select2.min.js', array( 'jquery' ), '3.4.1', false );
		wp_register_script( 'swp_admin_js',           $baseURL . 'assets/js/searchwp.js', array( 'jquery', 'select2' ), $this->version );
		wp_register_script( 'swp_progress',           $baseURL . 'assets/js/searchwp-progress.js', array( 'jquery' ), $this->version );

		// jqPlot
		wp_register_style( 'jqplotcss',               $baseURL . 'assets/vendor/jqplot/jquery.jqplot.min.css', false, '1.0.8' );
		wp_register_script( 'jqplotjs',               $baseURL . 'assets/vendor/jqplot/jquery.jqplot.min.js', array( 'jquery' ), '1.0.8' );
		wp_register_script( 'jqplotjs-barrenderer',   $baseURL . 'assets/vendor/jqplot/plugins/jqplot.barRenderer.min.js', array( 'jqplotjs' ), '1.0.8' );
		wp_register_script( 'jqplotjs-canvastext',    $baseURL . 'assets/vendor/jqplot/plugins/jqplot.canvasTextRenderer.min.js', array( 'jqplotjs' ), '1.0.8' );
		wp_register_script( 'jqplotjs-canvasaxis',    $baseURL . 'assets/vendor/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js', array( 'jqplotjs' ), '1.0.8' );
		wp_register_script( 'jqplotjs-axisrenderer',  $baseURL . 'assets/vendor/jqplot/plugins/jqplot.categoryAxisRenderer.min.js', array( 'jqplotjs' ), '1.0.8' );
		wp_register_script( 'jqplotjs-pointlabels',   $baseURL . 'assets/vendor/jqplot/plugins/jqplot.pointLabels.min.js', array( 'jqplotjs' ), '1.0.8' );

		// we only want our assets on our Settings page
		if ( $hook == 'settings_page_searchwp' ) {
			wp_enqueue_style( 'swp_admin_css' );
			wp_enqueue_style( 'select2' );

			wp_enqueue_script( 'underscore' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-tooltip' );
			wp_enqueue_script( 'select2' );

			wp_enqueue_script( 'swp_admin_js' );

			if( !isset( $_GET['nonce'] ) ) {
				// if a nonce was set we're dealing with advanced settings which might be purging the index
				// if this script were included the background process would be invoked, we don't want that right now
				wp_enqueue_script( 'swp_progress' );
				wp_localize_script( 'swp_progress', 'ajax_object',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'swpprogress' ) )
				);
			}
		}

		if ( 'dashboard_page_searchwp-stats' == $hook ) {
			wp_enqueue_script( 'jqplotjs' );
			wp_enqueue_script( 'jqplotjs-canvastext' );
			wp_enqueue_script( 'jqplotjs-canvasaxis' );
			// wp_enqueue_script( 'jqplotjs-barrenderer' );
			wp_enqueue_script( 'jqplotjs-axisrenderer' );
			// wp_enqueue_script( 'jqplotjs-pointlabels' );
			wp_enqueue_style( 'jqplotcss' );
			wp_enqueue_style( 'swp_stats_css' );
		}
	}


	/**
	 * Determines what percentage of indexing is complete. Polled via AJAX when viewing SearchWP settings page
	 *
	 * @since 1.0
	 */
	function getIndexProgress() {
		$totalPostsToIndex      = intval( get_option( SEARCHWP_PREFIX . 'total' ) );
		$remainingPostsToIndex  = intval( get_option( SEARCHWP_PREFIX . 'remaining' ) );
		$percentIndexComplete   = ( $totalPostsToIndex > 0 ) ? ( ( $totalPostsToIndex - $remainingPostsToIndex ) / $totalPostsToIndex ) * 100 : 0;

		flush();

		if ( ! empty( $remainingPostsToIndex ) ) {
			$progress = number_format( $percentIndexComplete, 2, '.', '' );
			do_action( 'searchwp_log', 'Indexer progress: ' . $progress );
			echo $progress;
		}
		else {
			do_action( 'searchwp_log', 'Index fully built' );
			echo - 1;
		}

		do_action( 'searchwp_log', 'Shutting down after retrieving index progress' );
		$this->shutdown();
		die();
	}


	/**
	 * Outputs the stats page and all stats
	 *
	 * @since 1.0
	 */
	function statsPage() {
		global $wpdb;

		?>
		<div class="wrap">

		<div id="icon-searchwp" class="icon32">
			<img src="<?php echo trailingslashit( $this->url ); ?>assets/images/searchwp@2x.png" alt="SearchWP" width="21" height="32" />
		</div>

		<h2><?php _e( 'Searches' ); ?></h2>

		<br />

		<div class="swp-searches-chart-wrapper">
			<div id="swp-searches-chart" style="width:100%;height:300px;"></div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {

				<?php
					// generate stats for the past 30 days for each search engine
					$prefix = $wpdb->prefix;
					if( isset( $this->settings['engines'] ) && is_array( $this->settings['engines'] ) && count( $this->settings['engines'] ) )
					{
						$engineLabels = array();
						$searchCounts = array();
						$engineCount = 1;
						foreach( $this->settings['engines'] as $engine => $engineSettings )
						{
							$sql = $wpdb->prepare( "
								SELECT DAY({$prefix}swp_log.tstamp) AS day, MONTH({$prefix}swp_log.tstamp) AS month, count({$prefix}swp_log.tstamp) AS searches
								FROM {$prefix}swp_log
								WHERE tstamp > DATE_SUB(NOW(), INTERVAL 30 day)
								AND {$prefix}swp_log.event = 'search'
								AND {$prefix}swp_log.engine = %s
								AND {$prefix}swp_log.query <> ''
								GROUP BY TO_DAYS({$prefix}swp_log.tstamp)
								ORDER BY {$prefix}swp_log.tstamp DESC
								", $engine );

							$searchCounts = $wpdb->get_results(
								$sql, 'OBJECT_K'
							);

							// key our array
							$searchesPerDay = array();
							for($i = 0; $i < 30; $i++)
								{$searchesPerDay[strtoupper(date( 'Md', strtotime( '-'. ( $i ) .' days' ) ))] = 0;
}

							if( is_array( $searchCounts ) && count( $searchCounts ) )
							{
								foreach( $searchCounts as $searchCount )
								{
									$count 		= intval( $searchCount->searches );
									$day 		= ( intval( $searchCount->day ) ) < 10 ? 0 . $searchCount->day : $searchCount->day;
									$month 		= ( intval( $searchCount->month ) ) < 10 ? 0 . $searchCount->month : $searchCount->month;
									$refdate 	= $month . '/01/' . date( 'Y' );
									$month 		= date( 'M', strtotime( $refdate ) );
									$key 		= strtoupper( $month . $day );

									$searchesPerDay[$key] = $count;
								}
							}

							$searchesPerDay = array_reverse( $searchesPerDay );

							echo 'var s' . $engineCount . ' = [';
							echo implode( ',', $searchesPerDay );
							echo '];';

							$engineLabel = "'";
							$engineLabel .= isset( $engineSettings['label'] ) ? $engineSettings['label'] : esc_attr__( 'Default', 'searchwp' );
							$engineLabel .= "'";
							$engineLabels[] = $engineLabel;

							$engineCount++;
						}
						$engineCount = 1;
						$engines = array();
						foreach( $this->settings['engines'] as $engine => $engineSettings )
						{
							$engines[] = 's' . $engineCount;
							$engineCount++;
						}
					?>
				plot = $.jqplot('swp-searches-chart', [<?php echo implode( ',', $engines ); ?>], {
					title            : '<?php esc_attr_e( 'Searches Performed in the Past 30 Days', 'searchwp' ); ?>',
					stackSeries      : false,
					captureRightClick: true,
					seriesDefaults   : {
						renderer       : $.jqplot.BarRenderer,
						rendererOptions: {
							barMargin         : 20,
							highlightMouseDown: false,
							shadowOffset      : 0,
							shadowDepth       : 0,
							shadowAlpha       : 0
						},
						pointLabels    : {show: true},
						lineWidth      : 2,
						shadow         : false
					},
					grid             : {
						drawGridlines: true,
						gridLineColor: '#f1f1f1',
						gridLineWidth: 1,
						borderWidth  : 1,
						shadow       : false,
						background   : '#fafafa',
						borderColor  : '#ffffff'
					},
					axes             : {
						xaxis: {
							renderer: $.jqplot.CategoryAxisRenderer
						},
						yaxis: {
							padMin: 0
						}
					},
					legend           : {
						show     : true,
						location : 'nw',
						placement: 'inside',
						labels   : [ <?php echo implode( ',', $engineLabels ); ?> ]
					}
				});

				<?php } ?>
			});
		</script>

		<div class="swp-group swp-stats swp-stats-4">

			<h2><?php _e( 'Popular Searches', 'searchwp' ); ?></h2>

			<div class="swp-stat postbox swp-meta-box metabox-holder">
				<h3 class="hndle"><span><?php _e( 'Today', 'searchwp' ); ?></span></h3>

				<div class="inside">
					<?php
					$sql = "
							SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
							FROM {$prefix}swp_log
							WHERE tstamp > DATE_SUB(NOW(), INTERVAL 1 DAY)
							AND {$prefix}swp_log.event = 'search'
							AND {$prefix}swp_log.query <> ''
							GROUP BY {$prefix}swp_log.query
							ORDER BY searchcount DESC
							LIMIT 10
						";

					$searchCounts = $wpdb->get_results(
						$sql
					);
					?>
					<?php if ( is_array( $searchCounts ) && ! empty( $searchCounts ) ) : ?>
						<table>
							<thead>
							<tr>
								<th><?php _e( 'Query', 'searchwp' ); ?></th>
								<th><?php _e( 'Searches', 'searchwp' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $searchCounts as $searchCount ) : ?>
								<tr>
									<td><?php echo esc_html( $searchCount->query ); ?></td>
									<td><?php echo $searchCount->searchcount; ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php else: ?>
						<p><?php _e( 'There have been no searches today.', 'searchwp' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="swp-stat postbox swp-meta-box metabox-holder">
				<h3 class="hndle"><span><?php _e( 'Week', 'searchwp' ); ?></span></h3>

				<div class="inside">
					<?php
					$sql = "
							SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
							FROM {$prefix}swp_log
							WHERE tstamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
							AND {$prefix}swp_log.event = 'search'
							AND {$prefix}swp_log.query <> ''
							GROUP BY {$prefix}swp_log.query
							ORDER BY searchcount DESC
							LIMIT 10
						";

					$searchCounts = $wpdb->get_results(
						$sql
					);
					?>
					<?php if ( is_array( $searchCounts ) && ! empty( $searchCounts ) ) : ?>
						<table>
							<thead>
							<tr>
								<th><?php _e( 'Query', 'searchwp' ); ?></th>
								<th><?php _e( 'Searches', 'searchwp' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $searchCounts as $searchCount ) : ?>
								<tr>
									<td><?php echo esc_html( $searchCount->query ); ?></td>
									<td><?php echo $searchCount->searchcount; ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php else: ?>
						<p><?php _e( 'There have been no searches within the past 7 days.', 'searchwp' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="swp-stat postbox swp-meta-box metabox-holder">
				<h3 class="hndle"><span><?php _e( 'Month', 'searchwp' ); ?></span></h3>

				<div class="inside">
					<?php
					$sql = "
							SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
							FROM {$prefix}swp_log
							WHERE tstamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
							AND {$prefix}swp_log.event = 'search'
							AND {$prefix}swp_log.query <> ''
							GROUP BY {$prefix}swp_log.query
							ORDER BY searchcount DESC
							LIMIT 10
						";

					$searchCounts = $wpdb->get_results(
						$sql
					);
					?>
					<?php if ( is_array( $searchCounts ) && ! empty( $searchCounts ) ) : ?>
						<table>
							<thead>
							<tr>
								<th><?php _e( 'Query', 'searchwp' ); ?></th>
								<th><?php _e( 'Searches', 'searchwp' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $searchCounts as $searchCount ) : ?>
								<tr>
									<td><?php echo esc_html( $searchCount->query ); ?></td>
									<td><?php echo $searchCount->searchcount; ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php else: ?>
						<p><?php _e( 'There have been no searches within the past 30 days.', 'searchwp' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="swp-stat postbox swp-meta-box metabox-holder">
				<h3 class="hndle"><span><?php _e( 'Year', 'searchwp' ); ?></span></h3>

				<div class="inside">
					<?php
					$sql = "
							SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
							FROM {$prefix}swp_log
							WHERE tstamp > DATE_SUB(NOW(), INTERVAL 365 DAY)
							AND {$prefix}swp_log.event = 'search'
							AND {$prefix}swp_log.query <> ''
							GROUP BY {$prefix}swp_log.query
							ORDER BY searchcount DESC
							LIMIT 10
						";

					$searchCounts = $wpdb->get_results(
						$sql
					);
					?>
					<?php if ( is_array( $searchCounts ) && ! empty( $searchCounts ) ) : ?>
						<table>
							<thead>
							<tr>
								<th><?php _e( 'Query', 'searchwp' ); ?></th>
								<th><?php _e( 'Searches', 'searchwp' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $searchCounts as $searchCount ) : ?>
								<tr>
									<td><?php echo esc_html( $searchCount->query ); ?></td>
									<td><?php echo $searchCount->searchcount; ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php else: ?>
						<p><?php _e( 'There have been no searches within the past year.', 'searchwp' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

		</div>

		<div class="swp-group swp-stats swp-stats-4">

			<h2><?php _e( 'Failed Searches', 'searchwp' ); ?></h2>

			<div class="swp-stat postbox swp-meta-box metabox-holder">
				<h3 class="hndle"><span><?php _e( 'Past 30 Days', 'searchwp' ); ?></span></h3>

				<div class="inside">
					<?php
					$sql = "
							SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
							FROM {$prefix}swp_log
							WHERE tstamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
							AND {$prefix}swp_log.event = 'search'
							AND {$prefix}swp_log.query <> ''
							AND {$prefix}swp_log.hits = 0
							GROUP BY {$prefix}swp_log.query
							ORDER BY searchcount DESC
							LIMIT 10
						";

					$searchCounts = $wpdb->get_results(
						$sql
					);
					?>
					<?php if ( is_array( $searchCounts ) && ! empty( $searchCounts ) ) : ?>
						<table>
							<thead>
							<tr>
								<th><?php _e( 'Query', 'searchwp' ); ?></th>
								<th><?php _e( 'Searches', 'searchwp' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $searchCounts as $searchCount ) : ?>
								<tr>
									<td><?php echo esc_html( $searchCount->query ); ?></td>
									<td><?php echo $searchCount->searchcount; ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php else: ?>
						<p><?php _e( 'There have been no failed searches within the past 30 days.', 'searchwp' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

		</div>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('.swp-stats').each(function () {
					var tallest = 0;
					$(this).find('.swp-stat > .inside').each(function () {
						if ($(this).height() > tallest) {
							tallest = $(this).height();
						}
					}).height(tallest);
				});
			});
		</script>

		</div>
	<?php
	}


	/**
	 * Truncates log table
	 *
	 * @since 1.6.5
	 */
	function resetStats() {
		global $wpdb;

		do_action( 'searchwp_log', 'resetStats()' );

		$prefix = $wpdb->prefix . SEARCHWP_DBPREFIX;

		// truncate the log table
		foreach ( $this->tables as $table ) {
			if( $table['table'] == 'log' ) {
				$tableName = $prefix . $table['table'];
				$wpdb->query( "TRUNCATE TABLE {$tableName}" );
			}
		}
	}


	/**
	 * Completely truncates all index tables, removes all index-related options
	 *
	 * @since 1.0
	 */
	function purgeIndex() {
		global $wpdb;

		do_action( 'searchwp_log', 'purgeIndex()' );

		$prefix = $wpdb->prefix . SEARCHWP_DBPREFIX;

		foreach ( $this->tables as $table ) {
			if( $table['table'] !== 'log' ) {
				$tableName = $prefix . $table['table'];
				$wpdb->query( "TRUNCATE TABLE {$tableName}" );
			}
		}

		// remove all metadata flags
		$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_' . SEARCHWP_PREFIX . 'indexed' ) );
		$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_' . SEARCHWP_PREFIX . 'last_index' ) );
		$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_' . SEARCHWP_PREFIX . 'attempts' ) );
		$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_' . SEARCHWP_PREFIX . 'skip' ) );
		$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_' . SEARCHWP_PREFIX . 'skip_doc_processing' ) );
		$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_' . SEARCHWP_PREFIX . 'review' ) );
		$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => SEARCHWP_PREFIX . 'content' ) );

		// kill all the options related to the index
		delete_option( SEARCHWP_PREFIX . 'running' );
		delete_option( SEARCHWP_PREFIX . 'total' );
		delete_option( SEARCHWP_PREFIX . 'remaining' );
		delete_option( SEARCHWP_PREFIX . 'done' );
		delete_option( SEARCHWP_PREFIX . 'last_activity' );
		delete_option( SEARCHWP_PREFIX . 'initial' );
		delete_option( SEARCHWP_PREFIX . 'initial_notified' );
		delete_option( SEARCHWP_PREFIX . 'valid_db_environment' );
		delete_transient( 'searchwp' );

		// reset the counts
		if( class_exists( 'SearchWPIndexer' ) ) {
			$indexer = new SearchWPIndexer();
			$indexer->updateRunningCounts();
		}
	}


	/**
	 * Activate license
	 *
	 * @return bool Whether the license was activated
	 * @since 1.0
	 */
	function activateLicense() {
		// listen for our activate button to be clicked
		if ( isset( $_POST['edd_license_activate'] ) ) {

			do_action( 'searchwp_log', 'activateLicense()' );

			// run a quick security check
			if ( ! check_admin_referer( 'edd_swp_nonce', 'edd_swp_nonce' ) ) {
				return false; // get out if we didn't click the Activate button
			}

			// retrieve the license from the database
			$license = trim( get_option( SEARCHWP_PREFIX . 'license_key' ) );

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( EDD_SEARCHWP_ITEM_NAME ) // the name of our product in EDD
			);

			// Call the custom API.
			$api_args = array(
				'timeout'   => 30,
				'sslverify' => false,
				'body'      => $api_params,
			);
			$response = wp_remote_post( EDD_SEARCHWP_STORE_URL, $api_args );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "active" or "inactive"
			update_option( SEARCHWP_PREFIX . 'license_status', $license_data->license );

			return true;
		}

		return false;
	}


	/**
	 * Check to see if we need to deactivate the license
	 *
	 * @return bool
	 * @since 1.0
	 */
	function deactivateLicenseCheck() {
		// listen for our activate button to be clicked
		if ( isset( $_POST['edd_license_deactivate'] ) ) {

			do_action( 'searchwp_log', 'deactivateLicenseCheck()' );

			// run a quick security check
			if ( ! check_admin_referer( 'edd_swp_nonce', 'edd_swp_nonce' ) ) {
				return false; // get out if we didn't click the Activate button
			}

			$this->deactivateLicense();

			return true;
		}

		return false;
	}


	/**
	 * Deactivate license
	 *
	 * @return bool
	 * @since 1.0
	 */
	function deactivateLicense() {
		do_action( 'searchwp_log', 'deactivateLicense()' );

		// retrieve the license from the database
		$license = trim( get_option( SEARCHWP_PREFIX . 'license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( EDD_SEARCHWP_ITEM_NAME ) // the name of our product in EDD
		);

		// Call the custom API.
		$api_args = array(
			'timeout'   => 30,
			'sslverify' => false,
			'body'      => $api_params,
		);
		$response = wp_remote_post( EDD_SEARCHWP_STORE_URL, $api_args );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if ( $license_data->license == 'deactivated' ) {
			delete_option( SEARCHWP_PREFIX . 'license_status' );
		}

		return true;
	}


	/**
	 * Output the markup for the license-specific settings page
	 *
	 * @since 1.0
	 */
	function licenseSettings() {
		?>
		<div class="wrap">
			<div id="icon-searchwp" class="icon32">
				<img src="<?php echo trailingslashit( $this->url ); ?>assets/images/searchwp@2x.png" alt="SearchWP" width="21" height="32" />
			</div>
			<h2><?php echo $this->pluginName . ' ' . __( 'License' ); ?></h2>

			<?php if ( ( $this->license !== false && $this->license !== '' ) && $this->status !== 'valid' ) : ?>
				<div id="setting-error-settings_updated" class="error settings-error">
					<p><?php _e( 'A license key was found, but it is <strong>inactive</strong>. Automatic updates <em>will not be available</em> until your license is activated.', 'searchwp' ); ?></p>
				</div>
			<?php endif; ?>

			<h3><?php _e( 'License Key', 'searchwp' ); ?></h3>

			<p><?php _e( 'Your license key was included in your purchase receipt.', 'searchwp' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( SEARCHWP_PREFIX . 'license' ); ?>
				<table class="form-table">
					<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e( 'License Key' ); ?>
						</th>
						<td>
							<input id="<?php echo SEARCHWP_PREFIX; ?>license_key" name="<?php echo SEARCHWP_PREFIX; ?>license_key" type="text" class="regular-text" value="<?php esc_attr_e( $this->license ); ?>" />
							<label class="description" for="<?php echo SEARCHWP_PREFIX; ?>license_key"><?php _e( 'Enter your license key', 'searchwp' ); ?></label>
						</td>
					</tr>
					<?php if ( false !== $this->license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'Activate License', 'searchwp' ); ?>
							</th>
							<td>
								<?php if ( $this->status !== false && $this->status == 'valid' ) { ?>
									<span style="color:green;"><?php _e( 'Active' ); ?></span>
									<?php wp_nonce_field( 'edd_swp_nonce', 'edd_swp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e( 'Deactivate License', 'searchwp' ); ?>" />
								<?php
								}
								else {
									wp_nonce_field( 'edd_swp_nonce', 'edd_swp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e( 'Activate License', 'searchwp' ); ?>" />
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
				<p>
					<a href="options-general.php?page=searchwp"><?php _e( 'Back to SearchWP Settings', 'searchwp' ); ?></a>
				</p>
			</form>
		</div>
	<?php
	}


	/**
	 * Output the markup for posts that failed to make it into the index
	 *
	 * @since 1.3
	 */
	function showErroneousPosts( $erroneousPosts ) {
		if( isset( $_GET['action'] ) && strtolower( $_GET['action'] ) == 'reintroduce' && isset( $_GET['swpid'] ) )
		{
			// remove the flags preventing the post from being indexed
			delete_post_meta( absint( $_GET['swpid'] ), '_' . SEARCHWP_PREFIX . 'skip' );
			delete_post_meta( absint( $_GET['swpid'] ), '_' . SEARCHWP_PREFIX . 'attempts' );
			$this->triggerIndex();
		}
		?>
		<div class="wrap">
			<div id="icon-searchwp" class="icon32">
				<img src="<?php echo trailingslashit( $this->url ); ?>assets/images/searchwp@2x.png" alt="SearchWP" width="21" height="32" />
			</div>
			<h2><?php echo $this->pluginName . ' ' . __( 'Outstanding Index Issues' ); ?></h2>
			<?php if( count( $erroneousPosts ) == 1 ) : ?>
				<p><?php _e( 'All applicable content has been indexed.', 'searchwp' ); ?></p>
			<?php else: ?>
				<p><?php _e( 'SearchWP was unable to index the following content, and it is actively being excluded from subsequent index runs.', 'searchwp' ); ?></p>
				<table class="swp-table swp-erroneous-posts">
					<colgroup>
						<col id="swp-erroneous-posts-titles" />
						<col id="swp-erroneous-posts-action" />
					</colgroup>
					<thead>
					<tr>
						<th><?php _e( 'Title', 'searchwp' ); ?></th>
						<th><?php _e( 'Reintroduce to indexer', 'searchwp' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach( $erroneousPosts as $erroneousPost ) : if( absint( $_GET['swpid'] ) != $erroneousPost ) : ?>
						<tr>
							<td><a href="<?php echo admin_url( 'post.php?post=' . $erroneousPost . '&action=edit' ); ?>"><?php echo get_the_title( $erroneousPost ); ?></a></td>
							<td><a href="options-general.php?page=searchwp&amp;nonce=<?php echo wp_create_nonce( 'swperroneous' ); ?>&action=reintroduce&swpid=<?php echo $erroneousPost; ?>"><?php _e( 'Reintroduce', 'searchwp' ); ?></a></td>
						</tr>
					<?php endif; endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<p>
				<a href="options-general.php?page=searchwp"><?php _e( 'Back to SearchWP Settings', 'searchwp' ); ?></a>
			</p>
		</div>
	<?php
	}


	/**
	 * Retrieves server and WordPress environment details
	 *
	 * @return array
	 * @since 1.3.3
	 */
	function getEnvironment() {
		global $wp_version, $wpdb;

		// also need to store plugin data now because core functions to retrieve it are admin-only
		$environment = array(
			'wp'      => $wp_version,
			'php'     => phpversion(),
			'mysql'   => $wpdb->db_version(),
			'server'  => $_SERVER['SERVER_SOFTWARE'],
			'plugins' => array()
		);

		if( function_exists( 'get_plugins' ) ) {
			$all_plugins = get_plugins();
			foreach( $all_plugins as $plugin_file => $plugin_data )
				if( is_plugin_active( $plugin_file ) )
					$environment['plugins'][] = $plugin_data['Name'] . ' ' . $plugin_data['Version'];
		}

		return $environment;
	}


	/**
	 * Store some metadata for remote debugging. Implemented this way because plugin information retrieval core functions
	 * are limited to the WP admin, and we need this to constantly update so as to keep in step to changes made after
	 * remote debugging is enabled (vs. tapping in to plugin activation/deactivation hooks)
	 *
	 * @since 1.3.3
	 */
	function updateRemoteMeta() {

		do_action( 'searchwp_log', 'updateRemoteMeta()' );

		// prep the default
		$remoteMeta = get_option( SEARCHWP_PREFIX . 'remote_meta' );
		if( !is_array( $remoteMeta ) )
			$remoteMeta = array();

		$remoteMeta['environment'] = $this->getEnvironment();

		update_option( SEARCHWP_PREFIX . 'remote_meta', $remoteMeta );
	}


	/**
	 * Output the markup for the advanced settings page
	 *
	 * @since 1.0
	 */
	function advancedSettings() {

		// do we need to purge the index?
		$purged = false;
		if (
				( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swpadvanced' ) ) &&
				( isset( $_REQUEST['action'] ) && wp_verify_nonce( $_REQUEST['action'], 'swppurgeindex' ) )
				&& current_user_can( 'manage_options' )
		) {
			do_action( 'searchwp_log', 'Passed nonce, purge index' );
			$this->purgeIndex();
			$purged = true;
		}

		// do we need to reset the stats?
		$resetStats = false;
		if (
			( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swpadvanced' ) ) &&
			( isset( $_REQUEST['action'] ) && wp_verify_nonce( $_REQUEST['action'], 'swppurgestats' ) )
			&& current_user_can( 'manage_options' )
		) {
			do_action( 'searchwp_log', 'Passed nonce, reset stats' );
			$this->resetStats();
			$resetStats = true;
		}

		// do we need to wake up the indexer?
		$wokenUp = false;
		if (
				( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swpadvanced' ) ) &&
				( isset( $_REQUEST['action'] ) && wp_verify_nonce( $_REQUEST['action'], 'swpwakeindexer' ) )
				&& current_user_can( 'manage_options' )
		) {
			do_action( 'searchwp_log', 'Waking up the indexer' );
			$running = get_option( SEARCHWP_PREFIX . 'running' );
			if( $running ) {
				do_action( 'searchwp_log', 'Resetting indexer' );
				delete_option( SEARCHWP_PREFIX . 'running' );
				delete_option( SEARCHWP_PREFIX . 'total' );
				delete_option( SEARCHWP_PREFIX . 'remaining' );
				delete_option( SEARCHWP_PREFIX . 'done' );
				delete_option( SEARCHWP_PREFIX . 'last_activity' );
			}
			$this->triggerIndex();
			$wokenUp = true;
		}

		// determine the remote debugging status
		$remoteDebug = get_option( SEARCHWP_PREFIX . 'remote' );
		if (
				( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swpadvanced' ) ) &&
				( isset( $_REQUEST['action'] ) && wp_verify_nonce( $_REQUEST['action'], 'swpremote' ) )
				&& current_user_can( 'manage_options' )
		) {
			if( $remoteDebug ) {
				do_action( 'searchwp_log', 'Turned off remote debugging' );
				delete_option( SEARCHWP_PREFIX . 'remote' );
				delete_option( SEARCHWP_PREFIX . 'remote_meta' );
				$remoteDebug = false;
			} else {
				do_action( 'searchwp_log', 'Turned on remote debugging' );
				$remoteDebugKey = sha1( site_url() . current_time( 'timestamp' ) );
				update_option( SEARCHWP_PREFIX . 'remote', $remoteDebugKey );
				$remoteDebug = $remoteDebugKey;
				$this->updateRemoteMeta();
			}
		}

		// determine the nuke status
		$nuke_on_delete = get_option( SEARCHWP_PREFIX . 'nuke_on_delete' );
		if (
			( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swpadvanced' ) ) &&
			( isset( $_REQUEST['action'] ) && wp_verify_nonce( $_REQUEST['action'], 'swpnuke' ) )
			&& current_user_can( 'manage_options' )
		) {
			if( $nuke_on_delete ) {
				delete_option( SEARCHWP_PREFIX . 'nuke_on_delete' );
				$nuke_on_delete = false;
			} else {
				update_option( SEARCHWP_PREFIX . 'nuke_on_delete', true );
				$nuke_on_delete = true;
			}
		}

		$nonce = isset( $_REQUEST['nonce'] ) ? $_REQUEST['nonce'] : '0';

		?>
		<div class="wrap">

			<div id="icon-searchwp" class="icon32">
				<img src="<?php echo trailingslashit( $this->url ); ?>assets/images/searchwp@2x.png" alt="SearchWP" width="21" height="32" />
			</div>

			<h2><?php echo $this->pluginName . ' ' . __( 'Advanced Settings' ); ?></h2>

			<?php if ( $purged ) : ?>
				<div id="setting-error-settings_updated" class="updated settings-error">
					<p><strong><?php _e( 'Index purged. <strong>The index will not be rebuilt until you initiate a reindex</strong>.', 'searchwp' ); ?></strong>
						<a href="options-general.php?page=searchwp"><?php _e( 'Initiate reindex', 'searchwp' ); ?></a></p>
				</div>
			<?php endif; ?>

			<?php if ( $resetStats ) : ?>
				<div id="setting-error-settings_updated" class="updated settings-error">
					<p><strong><?php _e( 'Search Stats have been reset', 'searchwp' ); ?></strong></p>
				</div>
			<?php endif; ?>

			<?php if ( $wokenUp ) : ?>
				<div id="setting-error-settings_updated" class="updated settings-error">
					<p><strong><?php _e( 'Attempted to wake up the indexer.', 'searchwp' ); ?></strong>
						<a href="options-general.php?page=searchwp"><?php _e( 'View indexer progress', 'searchwp' ); ?></a></p>
				</div>
			<?php endif; ?>

			<?php if ( $remoteDebug ) : ?>
				<div id="setting-error-settings_updated" class="updated settings-error">
					<p><?php _e( 'Remote Debugging is <strong>enabled</strong> with key', 'searchwp' ); ?> <code><?php echo $remoteDebug; ?></code></p>
				</div>
			<?php endif; ?>

			<?php if ( $nuke_on_delete ) : ?>
				<div id="setting-error-settings_updated" class="updated settings-error">
					<p><?php _e( 'Nuke on Delete is <strong>enabled</strong>', 'searchwp' ); ?></p>
				</div>
			<?php endif; ?>

			<h3><?php _e( 'Purge index', 'searchwp' ); ?></h3>
			<p style="padding-bottom:23px;">
				<?php _e( 'If you would like to <strong>completely wipe out the index and start fresh</strong>, you can do so.', 'searchwp' ); ?>
				<span class="description"><?php _e( 'Search statistics will be left as is', 'searchwp' ); ?></span>
				<a style="margin-left:13px;" class="button" id="swp-purge-index" href="options-general.php?page=searchwp&amp;nonce=<?php echo $nonce; ?>&amp;action=<?php echo wp_create_nonce( 'swppurgeindex' ); ?>"><?php _e( 'Purge Index', 'searchwp' ); ?></a>
			</p>

			<h3><?php _e( 'Reset search stats', 'searchwp' ); ?></h3>
			<p style="padding-bottom:23px;">
				<?php _e( 'If you would like to <strong>completely reset your Search Stats</strong>, you can do so.', 'searchwp' ); ?>
				<span class="description"><?php _e( 'Existing index will be left as is', 'searchwp' ); ?></span>
				<a style="margin-left:13px;" class="button" id="swp-reset-stats" href="options-general.php?page=searchwp&amp;nonce=<?php echo $nonce; ?>&amp;action=<?php echo wp_create_nonce( 'swppurgestats' ); ?>"><?php _e( 'Reset Stats', 'searchwp' ); ?></a>
			</p>

			<h3><?php _e( 'Pause Indexer', 'searchwp' ); ?></h3>
			<p style="padding-bottom:23px;">
				<?php _e( 'Pause the indexer. It will pick up where it left off when it resumes.', 'searchwp' ); ?>
				<a style="margin-left:13px;" class="button" id="swp-indexer-pause" href="options-general.php?page=searchwp&amp;nonce=<?php echo $nonce; ?>&amp;action=<?php echo wp_create_nonce( 'swppauseindexer' ); ?>"><?php _e( 'Toggle Indexer Pause', 'searchwp' ); ?></a>
			</p>

			<h3><?php _e( 'Wake Up Indexer', 'searchwp' ); ?></h3>
			<p style="padding-bottom:23px;">
				<?php _e( 'If you believe the indexer has stalled, you can try to wake it up.', 'searchwp' ); ?>
				<a style="margin-left:13px;" class="button" id="swp-indexer-wake" href="options-general.php?page=searchwp&amp;nonce=<?php echo $nonce; ?>&amp;action=<?php echo wp_create_nonce( 'swpwakeindexer' ); ?>"><?php _e( 'Wake Up Indexer', 'searchwp' ); ?></a>
			</p>

			<h3><?php _e( 'Remote Debugging', 'searchwp' ); ?> <span class="description"><?php _e( 'REQUIRES that this install to be publicly accessible', 'searchwp' ); ?></span></h3>
			<p style="padding-bottom:23px;">
				<?php _e( 'To better assist with support requests, SearchWP facilitates remote debugging.', 'searchwp' ); ?>
				<a style="margin-left:13px;" class="button" id="swp-toggle-remote" href="options-general.php?page=searchwp&amp;nonce=<?php echo $nonce; ?>&amp;action=<?php echo wp_create_nonce( 'swpremote' ); ?>"><?php _e( 'Toggle Remote Debugging', 'searchwp' ); ?></a>
			</p>

			<h3><?php _e( 'Nuke on Delete', 'searchwp' ); ?> <span class="description"><?php _e( 'Completely remove all traces of SearchWP when deleted via WordPress admin', 'searchwp' ); ?></span></h3>
			<p style="padding-bottom:23px;">
				<?php
					$nuke_message = __( 'SearchWP can completely remove all traces of itself when you choose to Delete it using the Plugin menu.', 'searchwp' );
					$nuke_button = __( 'Enable Nuke on Delete', 'searchwp' );
					if( $nuke_on_delete ) {
						$nuke_message = __( 'SearchWP has been configured to completely remove all traces of itself when you choose to Delete it using the Plugin menu.', 'searchwp' );
						$nuke_button = __( 'Disable Nuke on Delete', 'searchwp' );
					}
				?>
				<?php echo $nuke_message; ?>
				<a style="margin-left:13px;" class="button" id="swp-toggle-nuke" href="options-general.php?page=searchwp&amp;nonce=<?php echo $nonce; ?>&amp;action=<?php echo wp_create_nonce( 'swpnuke' ); ?>"><?php echo $nuke_button; ?></a>
			</p>

			<p style="padding-top:20px;">
				<a class="button-primary" href="options-general.php?page=searchwp"><?php _e( 'Back to Settings', 'searchwp' ); ?></a>
			</p>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$('#swp-purge-index').click(function () {
						if (confirm('<?php echo _e( "Are you SURE you want to delete the entire SearchWP index?", 'searchwp' ); ?>')) {
							return confirm('<?php echo _e( "Are you completely sure? THIS CAN NOT BE UNDONE!", 'searchwp' ); ?>');
						}
						return false;
					});
					$('#swp-reset-stats').click(function () {
						if (confirm('<?php echo _e( "Are you SURE you want to completely reset your Search Stats?", 'searchwp' ); ?>')) {
							return confirm('<?php echo _e( "Are you completely sure? THIS CAN NOT BE UNDONE!", 'searchwp' ); ?>');
						}
						return false;
					});
				});
			</script>
		</div>
	<?php
	}


	/**
	 * Callback for our implementation of add_options_page. Displays our options screen.
	 *
	 * @uses  wpdb
	 * @uses  get_option to get saved SearchWP settings
	 * @since 1.0
	 */
	function optionsPage() {
		global $wpdb;

		// check for erroneous posts that were not indexed after multiple attempts
		$args = array(
			'posts_per_page'        => -1,
			'post_type'             => 'any',
			'post_status'           => array( 'publish', 'inherit' ),
			'fields'                => 'ids',
			'meta_query'    => array(
				'relation'          => 'AND',
				array(
					'key'           => '_' . SEARCHWP_PREFIX . 'indexed',
					'value'         => '', // http://core.trac.wordpress.org/ticket/23268
					'compare'       => 'NOT EXISTS',
					'type'          => 'BINARY'
				),
				array(
					'key'           => '_' . SEARCHWP_PREFIX . 'skip',
					'value'         => '', // only want media that hasn't failed indexing multiple times
					'compare'       => 'EXISTS',
					'type'          => 'BINARY'
				)
			)
		);

		$erroneousPosts = get_posts( $args );

		// check to see if we need to display an extension settings page
		if ( ! empty( $this->extensions ) && isset( $_GET['nonce'] ) && isset( $_GET['extension'] ) ) {
			if ( wp_verify_nonce( $_GET['nonce'], 'swp_extension_' . $_GET['extension'] ) )
				foreach ( $this->extensions as $extension => $attributes ) // find out which extension we're working with
				{
					if ( isset( $attributes->slug ) && $attributes->slug === $_GET['extension'] ) {
						if ( method_exists( $this->extensions[$extension], 'view' ) ) {
							?>
							<div class="wrap" id="searchwp-<?php echo $attributes->slug; ?>-wrapper">
								<div id="icon-options-general" class="icon32"><br /></div>
								<div class="<?php echo $attributes->slug; ?>-container">
									<h2><?php _e( 'SearchWP', 'searchwp' ); ?> <?php echo $attributes->name; ?></h2>
									<?php $this->extensions[$extension]->view(); ?>
								</div>
								<p class="searchwp-extension-back">
									<a href="options-general.php?page=searchwp"><?php _e( 'Back to SearchWP Settings', 'searchwp' ); ?></a>
								</p>
							</div>
						<?php
						}
						break;
					}
				}
			return;
		}

		// check to see if we should show the license activation
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swpadvanced' ) && current_user_can( 'manage_options' ) ) {
			$this->advancedSettings();
			return;
		}

		// check to see if we should show posts that failed indexing
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'swperroneous' ) && current_user_can( 'manage_options' ) ) {
			$this->showErroneousPosts( $erroneousPosts );
			return;
		}

		// check to see if we should show the advanced settings screen
		if ( isset( $_REQUEST['activate'] ) && wp_verify_nonce( $_REQUEST['activate'], 'swpactivate' ) ) {
			$this->licenseSettings();
			return;
		}

		// retrieve the most commonly used custom field keys to include in the Custom Fields weight table select
		$this->keys = $wpdb->get_col( "
				SELECT meta_key
				FROM $wpdb->postmeta
				WHERE meta_key != '_" . SEARCHWP_PREFIX . "indexed'
				AND meta_key != '" . SEARCHWP_PREFIX . "content'
				AND meta_key != '_" . SEARCHWP_PREFIX . "needs_remote'
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

		$licenseNonceUrl = 'options-general.php?page=searchwp&amp;activate=' . wp_create_nonce( 'swpactivate' );
		?>
		<div class="wrap">
			<div id="icon-searchwp" class="icon32">
				<img src="<?php echo trailingslashit( $this->url ); ?>assets/images/searchwp@2x.png" alt="SearchWP" width="21" height="32" />
			</div>
			<h2>
				<?php echo $this->pluginName . ' ' . __( 'Settings' ); ?>
				<?php if ( false == $this->license ) { ?>
					<a class="button button-primary swp-activate-license" href="<?php echo $licenseNonceUrl; ?>"><?php _e( 'Activate License', 'searchwp' ); ?></a>
				<?php }
				else { ?>
					<a class="button swp-manage-license" href="<?php echo $licenseNonceUrl; ?>"><?php _e( 'Manage License', 'searchwp' ); ?></a>
				<?php } ?>
				<?php if ( ! empty( $this->extensions ) ) : ?>
					<div class="swp-menu-extensions swp-btn-group">
						<a class="button swp-btn swp-dropdown-toggle" data-toggle="dropdown" href="#">
							<?php _e( 'Extensions', 'searchwp' ); ?>
							<span class="swp-caret"></span>
						</a>
						<ul class="swp-dropdown-menu">
							<?php foreach ( $this->extensions as $extension ) : ?>
								<?php if ( !empty( $extension->public ) && isset( $extension->slug ) && isset( $extension->name ) ) : ?>
									<?php $nonce = wp_create_nonce( 'swp_extension_' . $extension->slug ); ?>
									<li><a href="options-general.php?page=searchwp&amp;extension=<?php echo $extension->slug; ?>&amp;nonce=<?php echo $nonce; ?>"><?php echo $extension->name; ?></a></li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</h2>

			<?php
			/**
			 * LICENSE CHECK
			 */
			if ( ( $this->license !== false && $this->license !== '' ) && $this->status !== 'valid' ) : ?>
				<div id="setting-error-settings_updated" class="error settings-error">
					<p><?php _e( 'A license key was found, but it is <strong>inactive</strong>. Automatic updates <em>will not be available</em> until your license is activated.', 'searchwp' ); ?> <a href="<?php echo $licenseNonceUrl; ?>"><?php _e( 'Manage License', 'searchwp' ); ?></a></p>
				</div>
			<?php endif; ?>

			<?php
			if ( isset( $_REQUEST['nnonce'] ) && wp_verify_nonce( $_REQUEST['nnonce'], 'swplicensenag' ) && current_user_can( 'manage_options' ) )
				update_option( SEARCHWP_PREFIX . 'license_nag', 1 );

			if ( $this->license == false && ! get_option( SEARCHWP_PREFIX . 'license_nag' ) && apply_filters( 'searchwp_initial_license_nag', true ) ) : ?>
				<div id="setting-error-settings_updated" class="updated settings-error swp-license-nag">
					<p><?php _e( 'In order to receive updates and support, you must have an active license.', 'searchwp' ); ?> <a href="<?php echo $licenseNonceUrl; ?>"><?php _e( 'Manage License', 'searchwp' ); ?></a> <a href="<?php echo EDD_SEARCHWP_STORE_URL; ?>"><?php _e( 'Purchase License', 'searchwp' ); ?></a> <a href="options-general.php?page=searchwp&amp;nnonce=<?php echo wp_create_nonce( 'swplicensenag' ); ?>"><?php _e( 'Dismiss', 'searchwp' ); ?></a></p>
				</div>
			<?php endif; ?>

			<?php
			/**
			 * MYSQL CHECK
			 */
			if ( isset( $_REQUEST['vnonce'] ) && wp_verify_nonce( $_REQUEST['vnonce'], 'swpmysqlnag' ) && current_user_can( 'manage_options' ) )
				update_option( SEARCHWP_PREFIX . 'mysql_version_nag', 1 );

			if ( ! version_compare( '5.1', $wpdb->db_version(), '<' )  && ! get_option( SEARCHWP_PREFIX . 'mysql_version_nag' ) ) : ?>
				<div class="updated settings-error">
					<p><?php echo sprintf( __( 'Your server is running MySQL version %1$s which may prevent search results from appearing due to <a href="http://bugs.mysql.com/bug.php?id=41156">bug 41156</a>. Please update MySQL to a more recent version (at least 5.1).', 'searchwp' ), $wpdb->db_version() ); ?> <a href="options-general.php?page=searchwp&amp;vnonce=<?php echo wp_create_nonce( 'swpmysqlnag' ); ?>"><?php _e( 'Dismiss', 'searchwp' ); ?></a></p>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">

				<div class="swp-wp-settings-api">
					<?php do_settings_sections( $this->textDomain ); ?>
					<?php settings_fields( SEARCHWP_PREFIX . 'settings' ); ?>
				</div>

				<?php $initial = get_option( SEARCHWP_PREFIX . 'initial' ); if ( !empty( $initial ) && false == get_option( SEARCHWP_PREFIX . 'initial_notified' ) ) : ?>
					<div class="updated">
						<p><?php _e( 'Initial index has been built', 'searchwp' ); ?></p>
					</div>
					<?php update_option( SEARCHWP_PREFIX . 'initial_notified', true ); ?>
				<?php endif; ?>

				<?php
				$remainingPostsToIndex = get_option( SEARCHWP_PREFIX . 'remaining' );
				if( !is_bool( $remainingPostsToIndex ) ) $remainingPostsToIndex = absint( $remainingPostsToIndex );
				?>
				<div class="updated settings-error swp-in-progress<?php if ( $remainingPostsToIndex === 0 ) : ?> swp-in-progress-done<?php endif; ?>">
					<div class="swp-progress-wrapper">
						<p class="swp-label"><?php _e( 'Indexing is', 'searchwp' ); ?>
							<span><?php _e( 'almost', 'searchwp' ); ?></span> <?php _e( 'complete', 'searchwp' ); ?>
							<a class="swp-tooltip" href="#swp-tooltip-progress">?</a></p>

						<div class="swp-tooltip-content" id="swp-tooltip-progress">
							<?php _e( 'This process is running in the background. You can leave this page and the index will continue to be built until completion.', 'searchwp' ); ?>
						</div>
						<div class="swp-progress-track">
							<div class="swp-progress-bar"></div>
						</div>
					</div>
				</div>

				<?php if( !empty( $erroneousPosts ) ) : ?>
					<div class="updated">
						<p><strong><?php echo count( $erroneousPosts ); ?></strong> <?php if( count( $erroneousPosts ) == 1 ) { _e( 'Posts failed to index', 'searchwp' ); } else { _e( 'Post failed to index', 'searchwp' ); } ?>. <a href="options-general.php?page=searchwp&amp;nonce=<?php echo wp_create_nonce( 'swperroneous' ); ?>"><?php _e( 'View details', 'searchwp' ); ?></a></p>
					</div>
				<?php endif; ?>

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

					<h3 class="hndle"><span><?php _e( 'Default Search Engine', 'searchwp' ); ?></span></h3>

					<div class="inside">

						<p><?php _e( 'These settings will override WordPress default searches. You can customize which post types are included in search results, attributing specific weights to various content types within each post type.', 'searchwp' ); ?>
							<a class="swp-tooltip" href="#swp-tooltip-overview">?</a></p>

						<div class="swp-tooltip-content" id="swp-tooltip-overview">
							<?php _e( "Only checked post types will be included in search results. If a post type isn't displayed, ensure <code>exclude_from_search</code> is set to false when registering it.", 'searchwp' ); ?>
						</div>
						<?php searchwpEngineSettingsTemplate( 'default' ); ?>

					</div>

				</div>

				<div class="postbox swp-meta-box metabox-holder swp-jqueryui">

					<h3 class="hndle"><span><?php _e( 'Supplemental Search Engines', 'searchwp' ); ?></span></h3>

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
			<?php
			if ( ! $this->indexing && isset( $_GET['page'] ) && $_GET['page'] == 'searchwp' ) {
				$this->indexing = true;
				$hash = sha1( uniqid( 'searchwpindex' ) );
				set_transient( 'searchwp', $hash );
				do_action( 'searchwp_log', 'AJAX triggerIndex() ' . trailingslashit( site_url() ) . '?swpnonce=' . $hash );
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($){
						$.get( '<?php echo trailingslashit( site_url() ); ?>', { swpnonce: '<?php echo $hash; ?>' } );
					});
				</script>
			<?php
			}
			?>
		</div>
	<?php
		do_action( 'searchwp_log', 'Shutting down after displaying settings screen' );
		$this->shutdown();
	}


	/**
	 * Register our settings with WordPress
	 *
	 * @uses  add_settings_section as per the WordPress Settings API
	 * @uses  add_settings_field as per the WordPress Settings API
	 * @uses  register_setting as per the WordPress Settings API
	 * @since 1.0
	 */
	function initSettings() {
		add_settings_section(
			SEARCHWP_PREFIX . 'settings',
			'SearchWP Settings',
			array( $this, 'settingsCallback' ),
			$this->textDomain
		);

		add_settings_field(
			SEARCHWP_PREFIX . 'settings_field',
			'Settings',
			array( $this, 'settingsFieldCallback' ),
			$this->textDomain,
				SEARCHWP_PREFIX . 'settings'
		);

		register_setting(
			SEARCHWP_PREFIX . 'settings',
				SEARCHWP_PREFIX . 'settings',
			array( $this, 'validateSettings' )
		);

		// licensing
		register_setting(
			SEARCHWP_PREFIX . 'license',
				SEARCHWP_PREFIX . 'license_key',
			array( $this, 'sanitizeLicense' )
		);
	}


	/**
	 * Set up WP cron job for maintenance actions
	 *
	 * @since 1.0
	 */
	function scheduleMaintenance() {
		if ( ! wp_next_scheduled( 'swp_maintenance' ) ) {
			wp_schedule_event( time(), 'daily', 'swp_maintenance' );
		}

		if ( ! wp_next_scheduled( 'swp_indexer' ) && ! get_option( SEARCHWP_PREFIX . 'initial' ) ) {
			wp_schedule_event( time(), 'swp_frequent', 'swp_indexer' );
		}
	}


	/**
	 * Too keep an eye on the initial index process, we're going to set up a five minute
	 * interval in WP cron
	 *
	 * @param $schedules
	 *
	 * @return mixed
	 * @since 1.0
	 */
	function addCustomCronInterval( $schedules ) {
		// only add this interval if the initial index has not been completed
		if ( ! isset( $schedules['swp_frequent'] ) && ! get_option( SEARCHWP_PREFIX . 'initial' ) ) {
			$schedules['swp_frequent'] = array(
				'interval' => 60 * 5,
				'display'  => __( 'SearchWP Frequent (Every five minutes until initial index is built)' )
			);
		}
		return $schedules;
	}


	/**
	 * Callback to WordPress' hourly cron job
	 *
	 * @since 1.0
	 */
	function doCron() {
		// if the initial index hasn't been completed, we're going to ping the indexer
		if ( ! get_option( SEARCHWP_PREFIX . 'initial' ) ) {
			// fire off a request to the index process
			do_action( 'searchwp_log', 'Request index (cron)' );
			$this->triggerIndex();
		}
	}


	/**
	 * Perform periodic maintenance
	 *
	 * @return bool
	 * @since 1.0
	 */
	function doMaintenance() {
		do_action( 'searchwp_log', 'doMaintenance()' );

		$license = trim( get_option( SEARCHWP_PREFIX . 'license_key' ) );

		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $license,
			'item_name'  => urlencode( EDD_SEARCHWP_ITEM_NAME )
		);

		$api_args = array(
			'timeout'   => 30,
			'sslverify' => false,
			'body'      => $api_params,
		);
		$response = wp_remote_post( EDD_SEARCHWP_STORE_URL, $api_args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data->license != 'valid' ) {
			do_action( 'searchwp_log', 'License not valid' );
			delete_option( SEARCHWP_PREFIX . 'license_status' );
		}

		$this->update_check();

		return true;
	}


	/**
	 * Perform a forced update check
	 *
	 * @since 1.8
	 */
	function check_update_check( $current_screen = null ) {

		$check_for_update = false;

		// forced update?
		if( isset( $_GET['swpupdate'] ) ) {
			if( wp_verify_nonce( $_GET['swpupdate'], 'swpupdatecheck' ) ) {
				if( current_user_can( 'update_plugins' ) ) {
					delete_site_transient( 'update_plugins' );
					$check_for_update = true;
				}
			}
		}

		// also display on WordPress Updates and Plugins screens
		if( is_object( $current_screen ) ) {
			if( isset( $current_screen->id ) ) {
				if( 'update-core' == $current_screen->id || 'plugins' == $current_screen->id ) {
					$check_for_update = true;
				}
			}
		}

		if( $check_for_update ) {
			$this->update_check();
		}
	}


	/**
	 * Check to see if an update is available
	 */
	function update_check() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			// load our custom updater
			include( dirname( __FILE__ ) . '/vendor/EDD_SL_Plugin_Updater.php' );
		}

		// retrieve our license key from the DB
		$license_key = trim( get_option( SEARCHWP_PREFIX . 'license_key' ) );

		// setup the updater
		$edd_updater = new EDD_SL_Plugin_Updater( EDD_SEARCHWP_STORE_URL, __FILE__, array(
				'version'   => SEARCHWP_VERSION,        // current version number
				'license'   => $license_key,            // license key (used get_option above to retrieve from DB)
				'item_name' => EDD_SEARCHWP_ITEM_NAME,  // name of this plugin
				'author'    => 'Jonathan Christopher'   // author of this plugin
			)
		);
	}


	/**
	 * Sanitize the license
	 *
	 * @param $new
	 *
	 * @return mixed
	 * @since 1.0
	 */
	function sanitizeLicense( $new ) {
		$old = get_option( SEARCHWP_PREFIX . 'license_key' );

		if ( $old && $old != $new )
			delete_option( SEARCHWP_PREFIX . 'license_status' ); // new license has been entered, so must reactivate

		return $new;
	}


	/**
	 * Callback from our call to register_setting() in $this->initSettings
	 *
	 * @param $input array The submitted $_POST data
	 *
	 * @return mixed array Validated array of settings
	 * @since 1.0
	 */
	function validateSettings( $input ) {
		$validSettings   = array();
		$validCategories = array( 'engines', ); // TODO: integrate license storage

		// make sure the input is an array
		if ( is_array( $input ) ) {
			// sift through our settings category
			foreach ( $input as $category => $categorySettings ) {
				// make sure the array key is sanitized
				$sanitizedCategory                 = sanitize_key( $category );
				$validSettings[$sanitizedCategory] = array();

				// only proceed if we have a valid settings category
				if ( in_array( $sanitizedCategory, $validCategories ) ) {
					// we're going to first handle any core settings
					switch ( $sanitizedCategory ) {
						case 'engines':
							foreach ( $categorySettings as $engineName => $engineSettings ) {
								$sanitizedEngineName = empty( $engineSettings['label'] ) ? sanitize_key( $engineName ) : str_replace( '-', '_', sanitize_title( $engineSettings['label'] ) );

								while ( isset( $validSettings[$sanitizedCategory][$sanitizedEngineName] ) ) {
									$sanitizedEngineName .= '_copy';
								}

								$validSettings[$sanitizedCategory][$sanitizedEngineName] = $this->sanitizeEngineSettings( $engineSettings );

								if ( ! empty( $engineSettings['label'] ) )
									$validSettings[$sanitizedCategory][$sanitizedEngineName]['label'] = sanitize_text_field( $engineSettings['label'] );
							}
							break;
					}

					// TODO: accommodate settings implemented by extensions
				}
			}
		}

		return $validSettings;
	}


	/**
	 * Make sure the submitted engine settings match expectations
	 *
	 * @param array $engineSettings
	 *
	 * @return array
	 * @since 1.0
	 */
	function sanitizeEngineSettings( $engineSettings = array() ) {
		$validEngineSettings = array();

		if ( is_array( $engineSettings ) ) {
			foreach ( $engineSettings as $postType => $postTypeSettings ) {
				if ( in_array( $postType, $this->postTypes ) ) {
					$validEngineSettings[$postType] = array();

					// store a proper 'enabled' setting
					$validEngineSettings[$postType]['enabled'] = isset( $postTypeSettings['enabled'] ) && $postTypeSettings['enabled'] ? true : false;

					// store proper weights
					if ( isset( $postTypeSettings['weights'] ) && is_array( $postTypeSettings['weights'] ) ) {
						$validEngineSettings[$postType]['weights'] = array();
						foreach ( $postTypeSettings['weights'] as $postTypeWeightKey => $weight ) {
							if ( in_array( $postTypeWeightKey, $this->validTypes ) ) {
								if ( ! is_array( $weight ) ) {
									$weight = strpos( (string) $weight, '.' ) ? floatval( $weight ) : intval( $weight );
									if( $weight < -1 ) $weight = -1;
									$validEngineSettings[$postType]['weights'][$postTypeWeightKey] = $weight;
								}
								else {
									// it's either a taxonomy or custom field, comprised of multiple weights
									$validEngineSettings[$postType]['weights'][$postTypeWeightKey] = array();
									foreach ( $weight as $contentName => $subweight ) // could just check to see if $contentName is 'tax' or 'cf'...
									{
										if ( ! is_array( $subweight ) ) {
											// taxonomy
											$weightKey = sanitize_text_field( $contentName );
											$subweight = strpos( (string) $subweight, '.' ) ? floatval( $subweight ) : intval( $subweight );
											if( $subweight < -1 ) $subweight = -1;
											$validEngineSettings[$postType]['weights'][$postTypeWeightKey][$weightKey] = $subweight;
										}
										else {
											// custom field
											$customFieldFlag = sanitize_text_field( $contentName );
											$weight = strpos( (string) $subweight['weight'], '.' ) ? floatval( $subweight['weight'] ) : intval( $subweight['weight'] );
											if( $weight < -1 ) $weight = -1;
											if ( isset( $subweight['metakey'] ) && isset( $subweight['weight'] ) ) {
												$validEngineSettings[$postType]['weights'][$postTypeWeightKey][$customFieldFlag] = array(
													'metakey' => sanitize_text_field( $subweight['metakey'] ),
													'weight'  => $weight
												);
											}
										}
									}
								}
							}
						}
					}

					// dynamically add our taxonomies to valid options array
					$taxonomies = get_object_taxonomies( $postType );
					if ( is_array( $taxonomies ) && count( $taxonomies ) )
						foreach ( $taxonomies as $taxonomy ) {
							$taxonomy             = get_taxonomy( $taxonomy );
							$this->validOptions[] = 'exclude_' . $taxonomy->name;
						}

					// store proper options
					if ( isset( $postTypeSettings['options'] ) && is_array( $postTypeSettings['options'] ) ) {
						foreach ( $postTypeSettings['options'] as $engineOptionName => $engineOptionValue ) {
							if ( in_array( $engineOptionName, $this->validOptions ) ) {
								if ( is_string( $engineOptionValue ) ) {
									$validEngineSettings[$postType]['options'][$engineOptionName] = sanitize_text_field( $engineOptionValue );
								}
								elseif ( is_array( $engineOptionValue ) ) {
									$validEngineSettings[$postType]['options'][$engineOptionName] = sanitize_text_field( implode( ',', $engineOptionValue ) );
								}
								else {
									$validEngineSettings[$postType]['options'][$engineOptionName] = serialize( $engineOptionValue );
								}
							}
						}
					}
				}
			}
		}

		return $validEngineSettings;
	}


	/**
	 * Callback from our call to add_settings_section() in $this->initSettings
	 *
	 * @since 1.0
	 */
	function settingsCallback() {

	}


	/**
	 * Callback from our call to add_settings_field() in $this->initSettings. Outputs our (hidden) input field to
	 * accommodate the Settings API
	 *
	 * @since 1.0
	 */
	function settingsFieldCallback() {
		?>
		<input type="text" name="<?php echo SEARCHWP_PREFIX; ?>settings" id="<?php echo SEARCHWP_PREFIX; ?>settings" value="SearchWP" />
	<?php
	}


	/**
	 * Purge a post from the index when it is edited
	 *
	 * @param $post_id The edited post
	 */
	function purgePostViaEdit( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( false !== wp_is_post_revision( $post_id ) )
			return;

		if ( ! isset( $this->purgeQueue[$post_id] ) ) {
			$this->purgeQueue[$post_id] = $post_id;
			do_action( 'searchwp_log', 'purgePostViaEdit() ' . $post_id );
		}
		else {
			do_action( 'searchwp_log', 'Prevented duplicate purge purgePostViaEdit() ' . $post_id );
		}
	}


	/**
	 * Removes all record of a post and it's content from the index and triggers a reindex
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	function purgePost( $post_id ) {
		global $wpdb;

		do_action( 'searchwp_log', 'purgePost() ' . $post_id );
		$this->purgeQueue[$post_id] = $post_id;

		// remote it from the index
		$wpdb->delete( $wpdb->prefix . SEARCHWP_DBPREFIX . 'index', array( 'post_id' => $post_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . SEARCHWP_DBPREFIX . 'tax', array( 'post_id' => $post_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . SEARCHWP_DBPREFIX . 'cf', array( 'post_id' => $post_id ), array( '%d' ) );

		// remove the postmeta
		delete_post_meta( $post_id, '_' . SEARCHWP_PREFIX . 'indexed' );
		delete_post_meta( $post_id, '_' . SEARCHWP_PREFIX . 'attempts' );
		delete_post_meta( $post_id, '_' . SEARCHWP_PREFIX . 'skip' );
		delete_post_meta( $post_id, '_' . SEARCHWP_PREFIX . 'review' );

		return true;
	}


	/**
	 * Callback for actions related to comments changing
	 *
	 * @uses $this->purgePost to clear out the post content from the index and trigger a reindex entirely
	 *
	 * @param $id
	 */
	function purgePostViaComment( $id ) {
		$comment   = get_comment( $id );
		$object_id = $comment->comment_post_ID;
		if ( ! isset( $this->purgeQueue[$object_id] ) ) {
			$this->purgeQueue[$object_id] = $object_id;
			do_action( 'searchwp_log', 'purgePostViaComment() ' . $object_id );
		}
		else {
			do_action( 'searchwp_log', 'Prevented duplicate purge purgePostViaComment() ' . $object_id );
		}
	}


	function purgePostViaTerm( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( false !== wp_is_post_revision( $object_id ) )
			return;

		// prevent repeated purging of the same post
		if ( ! isset( $this->purgeQueue[$object_id] ) ) {
			$this->purgeQueue[$object_id] = $object_id;
			do_action( 'searchwp_log', 'purgePostViaTerm() ' . $object_id );
		}
		else {
			do_action( 'searchwp_log', 'Prevented duplicate purge purgePostViaTerm() ' . $object_id );
		}
	}


	/**
	 * Trigger a reindex
	 */
	private function triggerReindex() {
		// check capabilities
		if (
			! current_user_can( 'edit_posts' ) &&
			! current_user_can( 'edit_pages' ) &&
			! current_user_can( 'manage_options' )
		) {
			do_action( 'searchwp_log', 'Failed capabilities check in triggerReindex()' );
			return false;
		}

		do_action( 'searchwp_log', 'Request index (reindex)' );
		$this->triggerIndex();

		return true;
	}


	/**
	 * Enable SearchWP textdomain
	 *
	 * @since 1.0
	 */
	function textdomain() {
		$locale = apply_filters( 'searchwp', get_locale(), $this->textDomain );
		$mofile = WP_LANG_DIR . '/' . $this->textDomain . '/' . $this->textDomain . '-' . $locale . '.mo';

		if ( file_exists( $mofile ) ) {
			load_textdomain( $this->textDomain, $mofile );
		} else {
			load_plugin_textdomain( $this->textDomain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	}


	/**
	 * Callback for plugin activation, outputs admin notice
	 *
	 * @since 1.0
	 */
	function activation() {
		if ( false == get_option( SEARCHWP_PREFIX . 'activated' ) ) {
			add_option( SEARCHWP_PREFIX . 'activated', 1 );
			?>
			<div class="updated">
				<p><?php _e( 'SearchWP has been activated and the index is now being built. <a href="options-general.php?page=searchwp">View progress and settings</a>', 'searchwp' ); ?></p>
			</div>
			<?php

			// trigger the initial indexing
			do_action( 'searchwp_log', 'Request index (activation)' );
			$this->triggerIndex();
		}
	}


	/**
	 * Register meta box for document content textarea
	 *
	 * @since 1.0
	 */
	function documentContentMetaBox() {
		add_meta_box(
			'searchwp_doc_content',
			__( 'SearchWP File Content', 'searchwp' ),
			array( $this, 'documentContentMetaBoxMarkup' ),
			'attachment'
		);
	}


	/**
	 * Output the markup for the document content meta box
	 *
	 * @param $post
	 *
	 * @since 1.0
	 */
	function documentContentMetaBoxMarkup( $post ) {
		$existingContent = get_post_meta( $post->ID, SEARCHWP_PREFIX . 'content', true );
		wp_nonce_field( 'searchwpdoc', 'searchwp_doc_nonce' );

		$supportedMimeTypes = array(
			'text/plain',
			'text/csv',
			'text/tab-separated-values',
			'text/calendar',
			'text/richtext',
			'text/css',
			'text/html',
			'application/pdf',
			'application/msword',
			'application/vnd.ms-powerpoint',
			'application/vnd.ms-write',
			'application/vnd.ms-excel',
			'application/vnd.ms-access',
			'application/vnd.ms-project',
			'application/vnd.openxmlformats-officedocument.wordprocessingml. document',
			'application/vnd.ms-word.document.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.wordprocessingml. template',
			'application/vnd.ms-word.template.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-excel.sheet.macroEnabled.12',
			'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'application/vnd.ms-excel.template.macroEnabled.12',
			'application/vnd.ms-excel.addin.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml. presentation',
			'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml. slideshow',
			'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.template',
			'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'application/vnd.ms-powerpoint.slide.macroEnabled.12',
			'application/onenote',
			'application/vnd.oasis.opendocument.text',
			'application/vnd.oasis.opendocument.presentation',
			'application/vnd.oasis.opendocument.spreadsheet',
			'application/vnd.oasis.opendocument.graphics',
			'application/vnd.oasis.opendocument.chart',
			'application/vnd.oasis.opendocument.database',
			'application/vnd.oasis.opendocument.formula',
			'application/wordperfect',
			'application/vnd.apple.keynote',
			'application/vnd.apple.numbers',
			'application/vnd.apple.pages',
		);

		if ( in_array( $post->post_mime_type, $supportedMimeTypes ) ) : ?>
			<p><?php _e( 'The content below will be indexed for this file. If you are experiencing unexpected search results, ensure accuracy here.', 'searchwp' ); ?></p>
			<textarea style="display:block;width:100%;height:300px;" name="searchwp_doc_content"><?php if ( $existingContent ) echo esc_textarea( $existingContent ); ?></textarea>
			<div style="display:none !important;overflow:hidden !important;">
				<textarea style="display:block;width:100%;height:300px;" name="searchwp_doc_content_original"><?php if ( $existingContent ) echo esc_textarea( $existingContent ); ?></textarea>
			</div>
		<?php else: ?>
			<p><?php _e( 'Only plain text files, PDFs, and office documents are supported at this time.', 'searchwp' ); ?></p>
		<?php endif;
	}


	/**
	 * Callback fired when saving documents, saves document content
	 *
	 * @param $post_id
	 *
	 * @since 1.0
	 */
	function documentContentSave( $post_id ) {
		// check capability
		if ( 'attachment' == $_REQUEST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		}
		else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;
		}

		// check intent
		if ( ! isset( $_POST['searchwp_doc_nonce'] ) || ! wp_verify_nonce( $_POST['searchwp_doc_nonce'], 'searchwpdoc' ) )
			return;

		$originalContent = isset( $_POST['searchwp_doc_content_original'] ) ? sanitize_text_field( $_POST['searchwp_doc_content_original'] ) : '';
		$editedContent   = isset( $_POST['searchwp_doc_content'] ) ? sanitize_text_field( $_POST['searchwp_doc_content'] ) : '';
		$alreadySkipped  = get_post_meta( $post_id, '_' . SEARCHWP_PREFIX . 'skip_doc_processing', true );

		// check to see if the doc content is different than what it was
		if ( $alreadySkipped || ( md5( $originalContent ) != md5( $editedContent ) ) ) {
			do_action( 'searchwp_log', 'File content was edited by hand, saving' );
			update_post_meta( $post_id, '_' . SEARCHWP_PREFIX . 'skip_doc_processing', true );
			update_post_meta( $post_id, SEARCHWP_PREFIX . 'content', $editedContent );
			$postID = false;
			if( isset( $this->post ) ) {
				$postID = $this->post->ID;
			} elseif( is_numeric( $post_id ) ) {
				$postID = $post_id;
			}
			if( $postID ) {
				// TODO: better handling of non-auto-indexed file formats ($this->post is not defined for those attachments)
				delete_post_meta( $postID, '_' . SEARCHWP_PREFIX . 'attempts' );
				delete_post_meta( $postID, '_' . SEARCHWP_PREFIX . 'skip' );
			}
		}

	}

}


/**
 * Deactivation routine
 */
if( ! function_exists( 'swp_deactivate' ) ) {
	function swp_deactivate() {
		// remove cron jobs
		$swp_maintenance_timestamp = wp_next_scheduled( 'swp_maintenance' );
		if( $swp_maintenance_timestamp ) {
			wp_unschedule_event( $swp_maintenance_timestamp, 'swp_maintenance' );
		}
		$swp_indexer_timestamp = wp_next_scheduled( 'swp_indexer' );
		if( $swp_indexer_timestamp ) {
			wp_unschedule_event( $swp_indexer_timestamp, 'swp_indexer' );
		}
	}
}

register_deactivation_hook( __FILE__, 'swp_deactivate' );


/**
 * The one true SearchWP
 *
 * @return SearchWP SearchWP singleton
 * @since 1.0
 */
if( ! function_exists( 'swp_init' ) ) {
	function swp_init() {
		global $searchwp;

		$searchwp = SearchWP::instance();
		return $searchwp;
	}
}

swp_init();
