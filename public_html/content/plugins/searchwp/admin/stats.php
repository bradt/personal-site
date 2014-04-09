<?php

global $wpdb;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();

if( ! is_admin() || ! current_user_can( apply_filters( 'searchwp_statistics_cap', 'publish_posts' ) ) || empty( $user_id ) ) {
	wp_die( __( 'Invalid request', 'searchwp' ) );
}

?><div class="wrap">

	<div id="icon-searchwp" class="icon32">
		<img src="<?php echo trailingslashit( $this->url ); ?>assets/images/searchwp@2x.png" alt="SearchWP" width="21" height="32" />
	</div>

	<h2><?php _e( 'Search Statistics', 'searchwp' ); ?></h2>

	<br />

	<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php foreach( $this->settings['engines'] as $engine => $engineSettings ) : ?>
			<?php
			$active_tab = '';
			$engine_label = isset( $engineSettings['label'] ) ? sanitize_text_field( $engineSettings['label'] ) : __( 'Default', 'searchwp' );
			if( ( isset( $_GET['tab'] ) && $engine == $_GET['tab'] ) || ( ! isset( $_GET['tab'] ) && 'default' == $engine ) ) {
				$active_tab = ' nav-tab-active';
			}
			?>
			<a href="<?php echo get_admin_url(); ?>index.php?page=searchwp-stats&amp;tab=<?php echo esc_attr( $engine ); ?>" class="nav-tab<?php echo $active_tab; ?>"><?php echo $engine_label; ?></a>
		<?php endforeach; ?>
	</h2>

	<br />

	<div class="swp-searches-chart-wrapper">
		<div id="swp-searches-chart" style="width:100%;height:300px;"></div>
	</div>

	<script type="text/javascript">
		jQuery(document).ready(function ($) {

			<?php
				// generate stats for the past 30 days for each search engine
				$prefix = $wpdb->prefix;
				$engine = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'default';

				if( isset( $this->settings['engines'][$engine] ) ) {
					$engineSettings = $this->settings['engines'][$engine];
					$searchCounts = array();

					// retrieve our counts for the past 30 days
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
					for($i = 0; $i < 30; $i++) {
						$searchesPerDay[strtoupper(date( 'Md', strtotime( '-'. ( $i ) .' days' ) ))] = 0;
					}

					if( is_array( $searchCounts ) && count( $searchCounts ) ) {
						foreach( $searchCounts as $searchCount ) {
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

					echo 'var s = [';
					echo implode( ',', $searchesPerDay );
					echo '];';

					$engineLabel = isset( $engineSettings['label'] ) ? $engineSettings['label'] : esc_attr__( 'Default', 'searchwp' );

					// dump out the necessary JavaScript vars
					?>
					plot = $.jqplot('swp-searches-chart', [s], {
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
							labels   : [<?php echo "'" . __( 'Search engine: ', 'searchwp' ) . $engineLabel . "'"; ?>]
						}
					});
					<?php
				}
			?>
		});
	</script>

	<div class="swp-group swp-stats swp-stats-4">

	<?php

	$ignored_queries = get_user_meta( get_current_user_id(), SEARCHWP_PREFIX . 'ignored_queries', true );
	if ( ! is_array( $ignored_queries ) ) {
		$ignored_queries = array();
	}

	// check to see if we need to ignore something
	if( isset( $_GET['nonce'] ) && isset( $_GET['ignore'] ) && wp_verify_nonce( $_GET['nonce'], 'swpstatsignore' ) ) {
		// retrieve the original query
		$query_hash = sanitize_text_field( $_GET['ignore'] );
		$ignore_sql = $wpdb->prepare( "SELECT {$prefix}swp_log.query  FROM {$prefix}swp_log  WHERE md5( {$prefix}swp_log.query ) = %s", $query_hash );
		$query_to_ignore = $wpdb->get_var( $ignore_sql );
		if( ! empty( $query_to_ignore ) ) {
			$ignored_queries[$query_hash] = $query_to_ignore;
		}
		update_user_meta( get_current_user_id(), SEARCHWP_PREFIX . 'ignored_queries', $ignored_queries );
	}

	$ignored_queries_sql = "'" . implode( "','", $ignored_queries ) . "'";
	$ignored_queries_sql_where = empty( $ignored_queries ) ? "AND {$prefix}swp_log.query <> ''" : "AND {$prefix}swp_log.query NOT IN ({$ignored_queries_sql})";

	// reset the nonce
	$ignore_nonce = wp_create_nonce( 'swpstatsignore' );

	?>

	<h2><?php _e( 'Popular Searches', 'searchwp' ); ?></h2>

	<script type="text/javascript">
		jQuery(document).ready(function($){
			var searchwp_resize_columns = function() {
				var searchwp_stat_width = $('.swp-stat:first').width();
				$('.swp-stats td div').css('max-width',Math.floor(searchwp_stat_width/2) - 10 );
			};
			searchwp_resize_columns();
			jQuery(window).resize(function(){
				searchwp_resize_columns();
			})
		});
	</script>

	<div class="swp-stat postbox swp-meta-box metabox-holder">
		<h3 class="hndle"><span><?php _e( 'Today', 'searchwp' ); ?></span></h3>

		<div class="inside">
			<?php
			$sql = $wpdb->prepare( "
					SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
					FROM {$prefix}swp_log
					WHERE tstamp > DATE_SUB(NOW(), INTERVAL 1 DAY)
					AND {$prefix}swp_log.event = 'search'
					AND {$prefix}swp_log.engine = %s
					{$ignored_queries_sql_where}
					GROUP BY {$prefix}swp_log.query
					ORDER BY searchcount DESC
					LIMIT 10
				", $engine );

			$searchCounts = $wpdb->get_results( $sql );
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
							<td>
								<div title="<?php echo esc_attr( $searchCount->query ); ?>">
									<a class="swp-delete" href="<?php echo get_admin_url(); ?>index.php?page=searchwp-stats&tab=<?php echo urlencode( $engine ); ?>&nonce=<?php echo $ignore_nonce; ?>&ignore=<?php echo md5( esc_attr( $searchCount->query ) ); ?>">x</a>
									<?php echo esc_html( $searchCount->query ); ?>
								</div>
							</td>
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
			$sql = $wpdb->prepare( "
					SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
					FROM {$prefix}swp_log
					WHERE tstamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
					AND {$prefix}swp_log.event = 'search'
					AND {$prefix}swp_log.engine = %s
					{$ignored_queries_sql_where}
					GROUP BY {$prefix}swp_log.query
					ORDER BY searchcount DESC
					LIMIT 10
				", $engine );

			$searchCounts = $wpdb->get_results( $sql );
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
							<td>
								<div title="<?php echo esc_attr( $searchCount->query ); ?>">
									<a class="swp-delete" href="<?php echo get_admin_url(); ?>index.php?page=searchwp-stats&tab=<?php echo urlencode( $engine ); ?>&nonce=<?php echo $ignore_nonce; ?>&ignore=<?php echo md5( esc_attr( $searchCount->query ) ); ?>">x</a>
									<?php echo esc_html( $searchCount->query ); ?>
								</div>
							</td>
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
			$sql = $wpdb->prepare( "
					SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
					FROM {$prefix}swp_log
					WHERE tstamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
					AND {$prefix}swp_log.event = 'search'
					AND {$prefix}swp_log.engine = %s
					{$ignored_queries_sql_where}
					GROUP BY {$prefix}swp_log.query
					ORDER BY searchcount DESC
					LIMIT 10
				", $engine );

			$searchCounts = $wpdb->get_results( $sql );
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
							<td>
								<div title="<?php echo esc_attr( $searchCount->query ); ?>">
									<a class="swp-delete" href="<?php echo get_admin_url(); ?>index.php?page=searchwp-stats&tab=<?php echo urlencode( $engine ); ?>&nonce=<?php echo $ignore_nonce; ?>&ignore=<?php echo md5( esc_attr( $searchCount->query ) ); ?>">x</a>
									<?php echo esc_html( $searchCount->query ); ?>
								</div>
							</td>
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
			$sql = $wpdb->prepare( "
					SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
					FROM {$prefix}swp_log
					WHERE tstamp > DATE_SUB(NOW(), INTERVAL 365 DAY)
					AND {$prefix}swp_log.event = 'search'
					AND {$prefix}swp_log.engine = %s
					{$ignored_queries_sql_where}
					GROUP BY {$prefix}swp_log.query
					ORDER BY searchcount DESC
					LIMIT 10
				", $engine );

			$searchCounts = $wpdb->get_results( $sql );
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
							<td>
								<div title="<?php echo esc_attr( $searchCount->query ); ?>">
									<a class="swp-delete" href="<?php echo get_admin_url(); ?>index.php?page=searchwp-stats&tab=<?php echo urlencode( $engine ); ?>&nonce=<?php echo $ignore_nonce; ?>&ignore=<?php echo md5( esc_attr( $searchCount->query ) ); ?>">x</a>
									<?php echo esc_html( $searchCount->query ); ?>
								</div>
							</td>
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
				$sql = $wpdb->prepare( "
						SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
						FROM {$prefix}swp_log
						WHERE tstamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
						AND {$prefix}swp_log.event = 'search'
						AND {$prefix}swp_log.engine = %s
						{$ignored_queries_sql_where}
						AND {$prefix}swp_log.hits = 0
						GROUP BY {$prefix}swp_log.query
						ORDER BY searchcount DESC
						LIMIT 10
					", $engine );

				$searchCounts = $wpdb->get_results( $sql );
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
								<td>
									<div title="<?php echo esc_attr( $searchCount->query ); ?>">
										<a class="swp-delete" href="<?php echo get_admin_url(); ?>index.php?page=searchwp-stats&tab=<?php echo urlencode( $engine ); ?>&nonce=<?php echo $ignore_nonce; ?>&ignore=<?php echo md5( esc_attr( $searchCount->query ) ); ?>">x</a>
										<?php echo esc_html( $searchCount->query ); ?>
									</div>
								</td>
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
			$('a.swp-delete').click(function(){
				if (confirm('<?php _e( "Are you sure you want to ignore this search from all statistics?", 'searchwp' ); ?>')) {
					return true;
				}else{
					return false;
				}
			});
		});
	</script>

</div>
