<?php

global $wpdb;

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'SWP' ) ) {
	exit;
}

if ( 'valid' !== $this->status ) {
	$ticket_create_url = "#TB_inline?width=600&height=550&inlineId=searchwp-support-ticket-create"; ?>
	<div id="searchwp-support-ticket-create" style="display:none;">
		<h2><?php _e( 'SearchWP Support', 'searchwp' ); ?></h2>
		<p><?php _e( 'Support is available only to <strong>active license holders</strong>. You must activate your license to receive support. If you do not have a license you may purchase one at any time.', 'searchwp' ); ?></p>
		<p><a class="button" href="<?php echo esc_url( admin_url( 'options-general.php?page=searchwp&amp;activate=' . wp_create_nonce( 'swpactivate' ) ) ); ?>"><?php _e( 'Activate License', 'searchwp' ); ?></a> <a class="button-primary" href="https://searchwp.com/buy/"><?php _e( 'Purchase License', 'searchwp' ); ?></a></p>
		<p class="swpnotice"><?php echo sprintf( __( 'If you cannot activate your license please see <a href="%s">this KB article</a>' , 'searchwp' ), 'https://searchwp.com/?p=29213' ); ?></p>
		<p class="description"><?php _e( 'If you are having difficulty activating your license or you believe you are receiving this notice in error, please <strong>include your license key</strong> in an email to' , 'searchwp' ); ?> <a href="mailto:support@searchwp.com">support@searchwp.com</a></p>
	</div>
<?php } else {
	$current_user = wp_get_current_user();

	$conflicts_var = '';
	$conflicts = new SearchWP_Conflicts();
	if ( ! empty( $conflicts->search_template_conflicts ) ) {
		// strip out the full disk path
		$search_template = str_replace( get_theme_root(), '', $conflicts->search_template );
		$conflicts_var = $search_template . ':';
		foreach ( $conflicts->search_template_conflicts as $line_number => $the_conflicts ) {
			$conflicts_var .= $line_number . ',';
		}
		$conflicts_var = substr( $conflicts_var, 0, strlen( $conflicts_var ) - 1 ); // trim off the trailing comma
	}

	$iframe_url = add_query_arg( array(
		'support'       => 1,
		'f'             => 6,
		'dd'            => 0,
		'dt'            => 0,
		'license'       => $this->license,
		'email'         => urlencode( $current_user->user_email ),
		'url'           => urlencode( home_url() ),
		'env'           => defined( 'WPE_APIKEY' ) ? 'wpe' : 0, // WP Engine has it's own set of problems so it's good to know right away
		'conflicts'     => urlencode( $conflicts_var ),
		'searchwp_v'    => urlencode( get_option( 'searchwp_version' ) ),
		'wp_v'          => urlencode( get_bloginfo( 'version' ) ),
		'php_v'         => urlencode( PHP_VERSION ),
		'mysql_v'       => urlencode( $wpdb->db_version() ),
		'TB_iframe'     => 'true',
		'width'         => 600,
		'height'        => 600,
	), 'https://searchwp.com/gfembed/' );
	$ticket_create_url = $iframe_url;
} ?>

<div class="swp-btn-group swp-preload">
	<a class="button swp-btn swp-btn-support thickbox" title="<?php _e( 'Create SearchWP Support Ticket', 'searchwp' ); ?>" href="<?php echo $ticket_create_url; ?>">
		<?php _e( 'Support', 'searchwp' ); ?>
	</a>
</div>

<style type="text/css">
	.swpnotice {
		text-align:center;
		border:1px solid #fae985;
		background:#FFF9D4;
		color:#424242;
		font-weight:bold;
		padding:1em;
		border-radius:1px;
	}
</style>