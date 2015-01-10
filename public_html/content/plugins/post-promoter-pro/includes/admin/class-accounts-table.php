<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Creates the Schedule List table for Post Promoter Pro
 */
class PPP_Accounts_Table extends WP_List_Table {

	/**
	 * Generate the Class from it's parent
	 */
	function __construct() {
		global $status, $page;

		parent::__construct( array(
				'singular'  => __( 'Social Media Service', 'ppp-txt' ),    //singular name of the listed records
				'plural'    => __( 'Social Media Services', 'ppp-txt' ),   //plural name of the listed records
				'ajax'      => false                                  //does this table support ajax?
			) );
	}

	/**
	 * What to show if no items are found
	 * @return void
	 */
	public function no_items() {
		_e( 'No Social Media Services Registered', 'ppp-txt' );
	}

	/**
	 * The Default columns
	 * @param  array $item        The Item being displayed
	 * @param  string $column_name The column we're currently in
	 * @return string              The Content to display
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * The columns for our list view
	 * @return array Columns shown on the Schedule page
	 */
	public function get_columns() {
		$columns = array(
			'icon'     => '',
			'avatar'   => __( 'Avatar', 'ppp-txt' ),
			'name'     => __( 'Connected As', 'ppp-txt' ),
			'actions'  => __( 'Actions', 'ppp-txt' ),
			'extras'   => __( 'Additional Info', 'ppp-txt' )
		);

		return $columns;
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

		$accounts = apply_filters( 'ppp_register_social_service', array() );

		foreach ( $accounts as $account ) {
			$data[$account] = array( 'icon'    => apply_filters( 'ppp_account_list_icon-' . $account, '&mdash;' ),
			                         'avatar'  => apply_filters( 'ppp_account_list_avatar-' . $account, '&mdash;' ),
			                         'name'    => apply_filters( 'ppp_account_list_name-' . $account, '&mdash;' ),
			                         'actions' => apply_filters( 'ppp_account_list_actions-' . $account, '&mdash;' ),
			                         'extras'  => apply_filters( 'ppp_account_list_extras-' . $account, '&mdash;' )
			                       );
		}

		$this->items = $data;
	}
}

$ppp_account_table = new PPP_Accounts_Table();



