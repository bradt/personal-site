<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class SearchWP_Stats {

	/**
	 * Retrieve searches within the past X days for a specific engine, ordered by number of searches
	 *
	 * @param array $args
	 *
	 * @internal param int $days How many days back to go
	 * @internal param string $engine The search engine
	 * @internal param array $exclude Search queries to skip
	 * @internal param int $limit The number of search queries to return
	 *
	 * @return mixed
	 */
	function get_popular_searches( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'days'    => 1,             // how many days to include
			'engine'  => 'default',     // the engine used
			'exclude' => array(),       // what queries to ignore
			'limit'   => 10,            // how many to return
		);

		// process our arguments
		$args = wp_parse_args( $args, $defaults );

		$prefix = $wpdb->prefix;
		$days = absint( $args['days'] );
		$limit = absint( $args['limit'] );

		// by default we want everything except empty searches
		$exclude_sql = "AND {$prefix}swp_log.query <> ''";

		// prepare the excludes if there are any
		if ( ! empty ( $args['exclude'] ) ) {
			foreach ( $args['exclude'] as $excluded_query_key => $excluded_query ) {
				$args['exclude'][ $excluded_query_key ] = $wpdb->prepare( '%s', $excluded_query );
			}
			$excluded_sql = implode( ',', $args['exclude'] );
			$exclude_sql = "AND {$prefix}swp_log.query NOT IN ({$excluded_sql})";
		}

		$sql = $wpdb->prepare( "
			SELECT {$prefix}swp_log.query, count({$prefix}swp_log.query) AS searchcount
			FROM {$prefix}swp_log
			WHERE tstamp > DATE_SUB(NOW(), INTERVAL {$days} DAY)
			AND {$prefix}swp_log.event = 'search'
			AND {$prefix}swp_log.engine = %s
			{$exclude_sql}
			GROUP BY {$prefix}swp_log.query
			ORDER BY searchcount DESC
			LIMIT {$limit}
		", $args['engine'] );

		return $wpdb->get_results( $sql );
	}

}
