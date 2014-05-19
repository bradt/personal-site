<?php

if ( !defined( 'ABSPATH' ) ) {
	// Silence is Golden
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Creates the Schedule List table for Post Promoter Pro
 */
class PPP_Schedule_Table extends WP_List_Table {

	/**
	 * Generate the Class from it's parent
	 */
	function __construct() {
		global $status, $page;

		parent::__construct( array(
				'singular'  => __( 'Scheduled Share', 'ppp-txt' ),    //singular name of the listed records
				'plural'    => __( 'Scheduled Shares', 'ppp-txt' ),   //plural name of the listed records
				'ajax'      => false                                  //does this table support ajax?
			) );
	}

	/**
	 * What to show if no items are found
	 * @return void
	 */
	public function no_items() {
		printf( __( 'No shares scheduled. Go <a href="%s">write something</a>!', 'ppp-txt' ), admin_url( 'post-new.php' ) );
	}

	/**
	 * The Default columns
	 * @param  array $item        The Item being displayed
	 * @param  string $column_name The column we're currently in
	 * @return string              The Content to display
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		case 'date':
		case 'content':
		case 'day':
		case 'post_title':
			return $item[ $column_name ];
		default:
			return;
		}
	}

	/**
	 * The columns for our list view
	 * @return array Columns shown on the Schedule page
	 */
	public function get_columns() {
		$columns = array(
			'post_title'     => __( 'Post Title', 'ppp-txt' ),
			'day'            => __( 'Day', 'ppp-txt' ),
			'date'           => __( 'Scheduled Date', 'ppp-txt' ),
			'content'        => __( 'Share Message', 'ppp-txt' )
		);

		return $columns;
	}

	/**
	 * Specifies how to display the post_id column on the schedule page
	 * @param  array $item The Item being displayed
	 * @return string       The HTML to display for this column.
	 */
	public function column_post_title( $item ) {
		$actions = array( 'edit'          => sprintf( __( '<a href="%s">Edit</a>', 'ppp-txt' ), admin_url( 'post.php?post=' . $item['post_id'] . '&action=edit#ppp_schedule_metabox' ) ),
		                  'delete'        => sprintf( __( '<a href="%s">Delete</a>', 'ppp-txt' ), admin_url( 'admin.php?page=ppp-schedule-info&action=delete_item&post_id=' . $item['post_id'] . '&name=' . $item['name'] . '&day=' . $item['day'] ) ),
		                  'share'         => sprintf( __( '<a href="%s">Share Now</a>', 'ppp-txt' ), admin_url( 'admin.php?page=ppp-schedule-info&action=share_now&post_id=' . $item['post_id'] . '&name=' . $item['name'] ) ),
		                  'share_delete'  => sprintf( __( '<a href="%s">Share Now & Delete</a>', 'ppp-txt' ), admin_url( 'admin.php?page=ppp-schedule-info&action=share_now&post_id=' . $item['post_id'] . '&name=' . $item['name'] . '&day=' . $item['day'] . '&delete_too=true' ) )
		                );

		return sprintf( '%1$s %2$s', $item['post_title'], $this->row_actions( $actions ) );
	}

	/**
	 * Specifies how to display the date columon on the schedule page
	 * @param  array $item The Item being displayed
	 * @return string       The HTML to display the date
	 */
	public function column_date( $item ) {
		return date_i18n( get_option('date_format') . ' @ ' . get_option('time_format'), $item['date'] );
	}

	/**
	 * Prepare the data for the WP List Table
	 * @return void
	 */
	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$data     = array();
		$sortable = false;
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = 25;
		$current_page = $this->get_pagenum();

		$crons = ppp_get_shceduled_crons();

		foreach ( $crons as $timestamp => $cron ) {
			$ppp_data   = $cron['ppp_share_post_event'];
			$array_keys = array_keys( $ppp_data );
			$hash_key   = $array_keys[0];
			$event_info = $ppp_data[$hash_key];
			$name_parts = explode( '_', $event_info['args'][1] );
			$day        = $name_parts[1];

			$data[$hash_key] = array(  'post_id'      => $event_info['args'][0],
				                       'post_title'   => get_the_title( $event_info['args'][0] ),
			                           'day'          => $day,
			                           'date'         => $timestamp + ( get_option( 'gmt_offset' ) * 3600 ),
			                           'content'      => ppp_build_share_message( $event_info['args'][0], $event_info['args'][1] ),
			                           'name'         => 'sharedate_' . $day . '_' . $event_info['args'][0] );
		}

		$total_items = count( $data );

		$offset = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;

		$data = array_slice( $data, ( $offset - 1 ) * $per_page, $per_page, true );
		$this->items = $data;


		$this->set_pagination_args( array(
				'total_items' => $total_items,                  //WE have to calculate the total number of items
				'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $per_page )
			) );

		$this->items = $data;
	}
}

$ppp_schedule_table = new PPP_Schedule_Table();



