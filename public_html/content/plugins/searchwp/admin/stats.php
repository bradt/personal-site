<?php

global $wpdb;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();

if ( ! is_admin() || ! current_user_can( apply_filters( 'searchwp_statistics_cap', 'publish_posts' ) ) || empty( $user_id ) ) {
	wp_die( __( 'Invalid request', 'searchwp' ) );
}

if ( isset( $_GET['tab'] ) ) {
	$engine = sanitize_text_field( $_GET['tab'] );
	if ( ! isset( $this->settings['engines'][ $engine ] ) ) {
		wp_die( __( 'Invalid request', 'searchwp' ) );
	}
}

?><div class="wrap">

	<div id="icon-searchwp" class="icon32">
		<img src="<?php echo trailingslashit( esc_url( $this->url ) ); ?>assets/images/searchwp@2x.png" alt="SearchWP" width="21" height="32" />
	</div>

	<h2><?php _e( 'Search Statistics', 'searchwp' ); ?></h2>

	<br />

	<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php foreach ( $this->settings['engines'] as $engine => $engineSettings ) : ?>
			<?php
			$active_tab = '';
			$engine_label = isset( $engineSettings['searchwp_engine_label'] ) ? sanitize_text_field( $engineSettings['searchwp_engine_label'] ) : __( 'Default', 'searchwp' );
			if ( ( isset( $_GET['tab'] ) && $engine == $_GET['tab'] ) || ( ! isset( $_GET['tab'] ) && 'default' == $engine ) ) {
				$active_tab = ' nav-tab-active';
			}
			?>
			<?php
				$the_link = admin_url( 'index.php?page=searchwp-stats' ) . '&tab=' . esc_attr( $engine );
			?>
			<a href="<?php echo esc_url( $the_link ); ?>" class="nav-tab<?php echo esc_attr( $active_tab ); ?>"><?php echo esc_html( $engine_label ); ?></a>
		<?php endforeach; ?>
	</h2>

	<br />

	<div class="swp-searches-chart-wrapper">
		<h3><?php _e( 'Searches over the past 30 days', 'searchwp' ); ?></h3>
		<div class="swp-searches-chart ct-chart"></div>
	</div>

	<!--suppress JSUnusedLocalSymbols -->
	<script type="text/javascript">
		jQuery(document).ready(function($) {

			<?php
			// generate stats for the past 30 days for each search engine
			$prefix = $wpdb->prefix;
			$engine = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'default';

			if ( isset( $this->settings['engines'][ $engine ] ) ) {
				$engineSettings = $this->settings['engines'][ $engine ];
				$searchCounts = array();

				// retrieve our counts for the past 30 days
				$sql = $wpdb->prepare( "-- noinspection SqlDialectInspection
					SELECT DAY({$prefix}swp_log.tstamp) AS day, MONTH({$prefix}swp_log.tstamp) AS month, count({$prefix}swp_log.tstamp) AS searches
					FROM {$prefix}swp_log
					WHERE tstamp > DATE_SUB(NOW(), INTERVAL 30 day)
					AND {$prefix}swp_log.event = 'search'
					AND {$prefix}swp_log.engine = %s
					AND {$prefix}swp_log.query <> ''
					GROUP BY TO_DAYS({$prefix}swp_log.tstamp)
					ORDER BY {$prefix}swp_log.tstamp DESC", $engine );

				$searchCounts = $wpdb->get_results(
					$sql, 'OBJECT_K'
				);

				// key our array
				$searchesPerDay = array();
				for ( $i = 0; $i <= 30; $i++ ) {
					$searchesPerDay[ strtoupper( date( 'Md', strtotime( '-'. ( $i ) .' days' ) ) ) ] = 0;
				}

				if ( is_array( $searchCounts ) && count( $searchCounts ) ) {
					foreach ( $searchCounts as $searchCount ) {
						$count 		= absint( $searchCount->searches );
						$day 		= ( intval( $searchCount->day ) ) < 10 ? 0 . absint( $searchCount->day ) : absint( $searchCount->day );
						$month 		= ( intval( $searchCount->month ) ) < 10 ? 0 . absint( $searchCount->month ) : absint( $searchCount->month );
						$refdate 	= $month . '/01/' . date( 'Y' );
						$month 		= date( 'M', strtotime( $refdate ) );
						$key 		= strtoupper( $month . $day );

						$searchesPerDay[ $key ] = absint( $count );
					}
				}

				$searchesPerDay = array_reverse( $searchesPerDay );

				// generate the x-axis labels
				$x_axis_labels = array();
				foreach ( $searchesPerDay as $day_key => $day_value ) {
					// keys are stored as 'Md' date format so we'll "decode"
					$x_axis_labels[] = intval( substr( $day_key, 3, 5 ) );
				}

				$engineLabel = isset( $engineSettings['searchwp_engine_label'] ) ? esc_attr( $engineSettings['searchwp_engine_label'] ) : esc_attr__( 'Default', 'searchwp' );

				// dump out the necessary JavaScript vars
				?>
				var chart_data = {
					labels: [<?php echo esc_js( implode( ',', $x_axis_labels ) ); ?>],
					series: [[<?php echo esc_js( implode( ',', $searchesPerDay ) ); ?>]]
				};
				var chart_options = {
					low: 0,
					showArea: true
				};

				function ordinal_suffix_of(i) {
					var j = i % 10,
						k = i % 100;
					if (j == 1 && k != 11) {
						return i + "st";
					}
					if (j == 2 && k != 12) {
						return i + "nd";
					}
					if (j == 3 && k != 13) {
						return i + "rd";
					}
					return i + "th";
				}

				var chart_responsive_options = [
					['screen and (min-width: 1251px)', {
						axisX: {
							labelInterpolationFnc: function(value) {
								value = ordinal_suffix_of(value);
								return value;
							}
						}
					}],
					['screen and (min-width: 751px) and (max-width: 1250px)', {
						axisX: {
							labelInterpolationFnc: function(value) {
								// only show every other day
								if(value%2){
									value = '';
								}else{
									value = ordinal_suffix_of(value);
								}
								return value;
							}
						}
					}],
					['screen and (max-width: 750px)', {
						axisX: {
							labelInterpolationFnc: function(value) {
								// hide the x axis labels
								return '';
							}
						}
					}]];
				Chartist.Line('.swp-searches-chart', chart_data, chart_options, chart_responsive_options );
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

	// we might need to update the format of $ignored_queries; 2.4.10 switched to all hashes (both keys and values)
	// to get around some edge cases of crazy search queries not being ignored
	// to check this we'll make sure the key matches the value and if it doesn't we'll run the update routine
	// this has to happen here because ignored queries are stored per-user
	if ( count( $ignored_queries ) ) {
		$ignored_queries_needs_update = true;
		foreach ( $ignored_queries as $key => $val ) {
			if ( $key == $val ) {
				$ignored_queries_needs_update = false;
				break;
			}
			$ignored_queries[ $key ] = md5( $val );
		}
		if ( $ignored_queries_needs_update ) {
			update_user_meta( get_current_user_id(), SEARCHWP_PREFIX . 'ignored_queries', $ignored_queries );
		}
	}

	// check to see if we need to ignore something
	if ( isset( $_GET['nonce'] ) && isset( $_GET['ignore'] ) && wp_verify_nonce( $_GET['nonce'], 'swpstatsignore' ) ) {
		// retrieve the original query
		$query_hash = sanitize_text_field( $_GET['ignore'] );
		$ignore_sql = $wpdb->prepare( "SELECT {$prefix}swp_log.query, md5( {$prefix}swp_log.query ) FROM {$prefix}swp_log  WHERE md5( {$prefix}swp_log.query ) = %s", $query_hash );
		$query_to_ignore = $wpdb->get_var( $ignore_sql );

		if ( ! empty( $query_to_ignore ) ) {
			$ignored_queries[ $query_hash ] = $query_hash;
		}

		update_user_meta( get_current_user_id(), SEARCHWP_PREFIX . 'ignored_queries', $ignored_queries );
		$this->reset_dashboard_stats_transients();
	}

	$ignored_queries_sql = "'" . implode( "','", $ignored_queries ) . "'";
	$ignored_queries_sql_where = empty( $ignored_queries ) ? "AND {$prefix}swp_log.query <> ''" : "AND md5( {$prefix}swp_log.query ) NOT IN ({$ignored_queries_sql})";

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
			});
		});
	</script>

	<div class="swp-stat postbox swp-meta-box metabox-holder">
		<h3 class="hndle"><span><?php _e( 'Today', 'searchwp' ); ?></span></h3>

		<div class="inside">
			<?php
			$today = date( 'Y-m-d', strtotime( current_time( 'mysql' ) ) ) . ' 00:00:00';
			$sql = $wpdb->prepare( "
					SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
					FROM {$prefix}swp_log
					WHERE tstamp > %s
					AND {$prefix}swp_log.event = 'search'
					AND {$prefix}swp_log.engine = %s
					{$ignored_queries_sql_where}
					GROUP BY {$prefix}swp_log.query
					ORDER BY searchcount DESC
					LIMIT 10
				", $today, $engine );

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
					<?php foreach ( $searchCounts as $searchCount ) : $query_hash = md5( $searchCount->query ); ?>
						<tr>
							<td>
								<div title="<?php echo esc_attr( $searchCount->query ); ?>">
									<?php
										$the_link = admin_url( 'index.php?page=searchwp-stats' ) . '&tab=' . esc_attr( $engine ) . '&nonce=' . esc_attr( $ignore_nonce ) . '&ignore=' . esc_attr( $query_hash );
									?>
									<a class="swp-delete" href="<?php echo esc_url( $the_link ); ?>">x</a>
									<?php echo esc_html( $searchCount->query ); ?>
								</div>
							</td>
							<td><?php echo absint( $searchCount->searchcount ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
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
					<?php foreach ( $searchCounts as $searchCount ) : $query_hash = md5( $searchCount->query ); ?>
						<tr>
							<td>
								<div title="<?php echo esc_attr( $searchCount->query ); ?>">
									<?php
										$the_link = admin_url( 'index.php?page=searchwp-stats' ) . '&tab=' . esc_attr( $engine ) . '&nonce=' . esc_attr( $ignore_nonce ) . '&ignore=' . esc_attr( $query_hash );
									?>
									<a class="swp-delete" href="<?php echo esc_url( $the_link ); ?>">x</a>
									<?php echo esc_html( $searchCount->query ); ?>
								</div>
							</td>
							<td><?php echo absint( $searchCount->searchcount ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
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
					<?php foreach ( $searchCounts as $searchCount ) : $query_hash = md5( $searchCount->query ); ?>
						<tr>
							<td>
								<div title="<?php echo esc_attr( $searchCount->query ); ?>">
									<?php
										$the_link = admin_url( 'index.php?page=searchwp-stats' ) . '&tab=' . esc_attr( $engine ) . '&nonce=' . esc_attr( $ignore_nonce ) . '&ignore=' . esc_attr( $query_hash );
									?>
									<a class="swp-delete" href="<?php echo esc_url( $the_link ); ?>">x</a>
									<?php echo esc_html( $searchCount->query ); ?>
								</div>
							</td>
							<td><?php echo absint( $searchCount->searchcount ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
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
					<?php foreach ( $searchCounts as $searchCount ) : $query_hash = md5( $searchCount->query ); ?>
						<tr>
							<td>
								<div title="<?php echo esc_attr( $searchCount->query ); ?>">
									<?php
										$the_link = admin_url( 'index.php?page=searchwp-stats' ) . '&tab=' . esc_attr( $engine ) . '&nonce=' . esc_attr( $ignore_nonce ) . '&ignore=' . esc_attr( $query_hash );
									?>
									<a class="swp-delete" href="<?php echo esc_url( $the_link ); ?>">x</a>
									<?php echo esc_html( $searchCount->query ); ?>
								</div>
							</td>
							<td><?php echo absint( $searchCount->searchcount ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
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
						<?php foreach ( $searchCounts as $searchCount ) : $query_hash = md5( $searchCount->query ); ?>
							<tr>
								<td>
									<div title="<?php echo esc_attr( $searchCount->query ); ?>">
										<?php
											$the_link = admin_url( 'index.php?page=searchwp-stats' ) . '&tab=' . esc_attr( $engine ) . '&nonce=' . esc_attr( $ignore_nonce ) . '&ignore=' . esc_attr( $query_hash );
										?>
										<a class="swp-delete" href="<?php echo esc_url( $the_link ); ?>">x</a>
										<?php echo esc_html( $searchCount->query ); ?>
									</div>
								</td>
								<td><?php echo absint( $searchCount->searchcount ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php _e( 'There have been no failed searches within the past 30 days.', 'searchwp' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

	</div>

	<script type="text/javascript">
		jQuery(document).ready(function ($) {
			$('.swp-stats').each(function () {
				var tallest = 0;
				$(this).find('.swp-stat').each(function () {
					if ($(this).outerHeight() > tallest) {
						tallest = $(this).outerHeight();
					}
				}).outerHeight(tallest);
			});
			$('a.swp-delete').click(function(){
				return !!confirm('<?php echo esc_js( __( 'Are you sure you want to ignore this search from all statistics?', 'searchwp' ) ); ?>');
			});
		});
	</script>

</div>
