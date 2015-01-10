<?php
/**
 * Weclome Page Class
 *
 * @package     PPP
 * @subpackage  Admin/Welcome
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * Adapted for Post Promoter Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PPP_Welcome Class
 *
 * A general class for About and Credits page.
 *
 * @since 1.2
 */
class PPP_Welcome {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'manage_options';

	/**
	 * Get things started
	 *
	 * @since 1.2
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome and Credits pages.
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function admin_menus() {
		// About Page
		add_dashboard_page(
			__( 'Welcome to Post Promoter Pro', 'ppp-txt' ),
			__( 'Welcome to Post Promoter Pro', 'ppp-txt' ),
			$this->minimum_capability,
			'ppp-about',
			array( $this, 'about_screen' )
		);

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with Post Promoter Pro', 'ppp-txt' ),
			__( 'Getting started with Post Promoter Pro', 'ppp-txt' ),
			$this->minimum_capability,
			'ppp-getting-started',
			array( $this, 'getting_started_screen' )
		);

	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'ppp-about' );
		remove_submenu_page( 'index.php', 'ppp-getting-started' );

		// Badge for welcome page
		$badge_url = PPP_URL . 'includes/images/ppp-badge.png';
		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/
		.ppp-badge {
			padding-top: 150px;
			height: 50px;
			width: 300px;
			color: #666;
			font-weight: bold;
			font-size: 14px;
			text-align: center;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
			margin: 0 -5px;
			background: url('<?php echo $badge_url; ?>') no-repeat;
		}

		.about-wrap .ppp-badge {
			position: absolute;
			top: 0;
			right: 0;
		}

		.ppp-welcome-screenshots {
			float: right;
			margin-left: 10px!important;
		}
		/*]]>*/
		</style>
		<?php
	}

	/**
	 * Navigation tabs
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function tabs() {
		$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'ppp-about';
		?>
		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo $selected == 'ppp-about' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'ppp-about' ), 'index.php' ) ) ); ?>">
				<?php _e( "What's New", 'ppp-txt' ); ?>
			</a>
			<!--
			<a class="nav-tab <?php echo $selected == 'ppp-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'ppp-getting-started' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Getting Started', 'ppp-txt' ); ?>
			</a>
			-->
		</h2>
		<?php
	}

	/**
	 * Render About Screen
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function about_screen() {
		list( $display_version ) = explode( '-', PPP_VERSION );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Post Promoter Pro %s', 'ppp-txt' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'The most effective way to promote your WordPress content.', 'ppp-txt' ), $display_version ); ?></div>
			<div class="ppp-badge"><?php printf( __( 'Version %s', 'ppp-txt' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<div class="changelog">
				<h3><?php _e( 'Improved Social Network Integration', 'ppp-txt' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PPP_URL . '/includes/images/screenshots/new-account-management.jpg'; ?>" class="ppp-welcome-screenshots"/>

					<h4><?php _e( 'Redesigned and Expanded', 'ppp-txt' );?></h4>
					<p><?php _e( 'Twitter, Facebook, and LinkedIn support is built into the core plugin.', 'ppp-txt' );?></p>
					<p><?php _e( 'We\'ve redesigned the account management screen to be more like the native WordPress experiance. Managed all your connections from one panel, no more tabs!', 'ppp-txt' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Now With Facebook Support!', 'ppp-txt' );?></h3>

				<div class="feature-section">

					<img src="<?php echo PPP_URL . '/includes/images/screenshots/facebook-support.jpg'; ?>" class="ppp-welcome-screenshots"/>

					<h4><?php _e( 'Expand your reach','ppp-txt' );?></h4>
					<p><?php _e( 'It has been the #1 most requested feature, and finally it is here...Facebook Support!', 'ppp-txt' );?></p>
					<p><?php _e( 'Share your posts to your timeline, or the timeline of a page you have publish permissions to. We have setup a beautiful thumbnail that integrates perfectly with Facebook.', 'ppp-txt' );?></p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Additional Updates', 'ppp-txt' );?></h3>

				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'Better debugging', 'ppp-txt' );?></h4>
						<p><?php _e( 'When you check to have debug enabled now, you will get some additional information about your social accounts on the account view.', 'ppp-txt' );?></p>

						<h4><?php _e( 'Improved LinkedIn Support', 'ppp-txt' );?></h4>
						<p><?php _e( 'Fixed a few issues with LinkedIn token expiration. If you have issues, just disconnect and reconnect.', 'ppp-txt' );?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Performance Improvements', 'ppp-txt' );?></h4>
						<p><?php _e( 'Made some improvements to the way Post Promoter Pro uses sessions, to help with overall site performance', 'ppp-txt' );?></p>
					</div>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ppp-options' ) ); ?>"><?php _e( 'Start Using Post Promoter Pro!', 'ppp-txt' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Getting Started Screen
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function getting_started_screen() {
		list( $display_version ) = explode( '-', PPP_VERSION );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to Post Promoter Pro %s', 'ppp-txt' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'The most effective way to promote your WordPress content.', 'ppp-txt' ), $display_version ); ?></div>
			<div class="ppp-badge"><?php printf( __( 'Version %s', 'ppp-txt' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<p class="about-description"><?php _e( 'Post Promoter Pro makes sharing your content as easy as possible.', 'ppp-txt' ); ?></p>

		</div>
		<?php
	}

	/**
	 * Sends user to the Welcome page on first activation of PPP as well as each
	 * time PPP is upgraded to a new version
	 *
	 * @access public
	 * @since 1.2
	 * @return void
	 */
	public function welcome() {

		// Bail if no activation redirect
		if ( ! get_transient( '_ppp_activation_redirect' ) )
			return;

		// Delete the redirect transient
		delete_transient( '_ppp_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) )
			return;

		wp_safe_redirect( admin_url( 'index.php?page=ppp-about' ) ); exit;
	}
}
new PPP_Welcome();
