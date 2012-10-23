<?php
/* 
Plugin Name: InfiniteWP - Client
Plugin URI: http://infinitewp.com/
Description: This is the client plugin of InfiniteWP that communicates with the InfiniteWP Admin panel.
Author: Revmakx
Version: 1.1.2
Author URI: http://www.revmakx.com
*/
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/

/*************************************************************
 * 
 * init.php
 * 
 * Initialize the communication with master
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/

if(!defined('IWP_MMB_CLIENT_VERSION'))
	define('IWP_MMB_CLIENT_VERSION', '1.1.2');


if ( !defined('IWP_MMB_XFRAME_COOKIE')){
	$siteurl = function_exists('get_site_option') ? get_site_option( 'siteurl' ) : get_option('siteurl');
	define('IWP_MMB_XFRAME_COOKIE', $xframe = 'wordpress_'.md5($siteurl).'_xframe');
}
global $wpdb, $iwp_mmb_plugin_dir, $iwp_mmb_plugin_url, $wp_version, $iwp_mmb_filters, $_iwp_mmb_item_filter;
if (version_compare(PHP_VERSION, '5.0.0', '<')) // min version 5 supported
    exit("<p>InfiniteWP Client plugin requires PHP 5 or higher.</p>");


$iwp_mmb_wp_version = $wp_version;
$iwp_mmb_plugin_dir = WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__));
$iwp_mmb_plugin_url = WP_PLUGIN_URL . '/' . basename(dirname(__FILE__));

require_once("$iwp_mmb_plugin_dir/helper.class.php");
require_once("$iwp_mmb_plugin_dir/core.class.php");
require_once("$iwp_mmb_plugin_dir/stats.class.php");
require_once("$iwp_mmb_plugin_dir/backup.class.php");
require_once("$iwp_mmb_plugin_dir/installer.class.php");
require_once("$iwp_mmb_plugin_dir/addons/manage_users/user.class.php");
require_once("$iwp_mmb_plugin_dir/addons/backup_repository/backup_repository.class.php");
require_once("$iwp_mmb_plugin_dir/api.php");
require_once("$iwp_mmb_plugin_dir/plugins/search/search.php");
require_once("$iwp_mmb_plugin_dir/plugins/cleanup/cleanup.php");


if( !function_exists ( 'iwp_mmb_filter_params' )) {
	function iwp_mmb_filter_params( $array = array() ){
		
		$filter = array( 'current_user', 'wpdb' );
		$return = array();
		foreach ($array as $key => $val) { 
			if( !is_int($key) && in_array($key, $filter) )
				continue;
				
			if( is_array( $val ) ) { 
				$return[$key] = iwp_mmb_filter_params( $val );
			} else {
				$return[$key] = $val;
			}
		} 
		
		return $return;
	}
}

if( !function_exists ('iwp_mmb_parse_request')) {
	function iwp_mmb_parse_request()
	{
		
		if (!isset($HTTP_RAW_POST_DATA)) {
			$HTTP_RAW_POST_DATA = file_get_contents('php://input');
		}
		ob_start();
		
		global $current_user, $iwp_mmb_core, $new_actions, $wp_db_version, $wpmu_version, $_wp_using_ext_object_cache;
		$data = base64_decode($HTTP_RAW_POST_DATA);
		if ($data){
			//$num = @extract(unserialize($data));
			$unserialized_data = unserialize($data);
			if(isset($unserialized_data['params'])){ 
				$unserialized_data['params'] = iwp_mmb_filter_params($unserialized_data['params']);
			}
			
			$iwp_action 	= $unserialized_data['iwp_action'];
			$params 		= $unserialized_data['params'];
			$id 			= $unserialized_data['id'];
			$signature 		= $unserialized_data['signature'];
		}
		
		if (isset($iwp_action)) {
			$action = $iwp_action;
			$_wp_using_ext_object_cache = false;
			@set_time_limit(600);
			
			if (!$iwp_mmb_core->check_if_user_exists($params['username']))
				iwp_mmb_response('Username <b>' . $params['username'] . '</b> does not have administrative access. Enter the correct username in the site options.', false);
			
			if ($action == 'add_site') {
				iwp_mmb_add_site($params);
				iwp_mmb_response('You should never see this.', false);
			}

			$auth = $iwp_mmb_core->authenticate_message($action . $id, $signature, $id);
			if ($auth === true) {
				
				if(isset($params['username']) && !is_user_logged_in()){
					$user = function_exists('get_user_by') ? get_user_by('login', $params['username']) : get_userdatabylogin( $params['username'] );
					wp_set_current_user($user->ID);
				}
				
				/* in case database upgrade required, do database backup and perform upgrade ( wordpress wp_upgrade() function ) */
				if( strlen(trim($wp_db_version)) && !defined('ACX_PLUGIN_DIR') ){
					if ( get_option('db_version') != $wp_db_version ) {
						/* in multisite network, please update database manualy */
						if (empty($wpmu_version) || (function_exists('is_multisite') && !is_multisite())){
							if( ! function_exists('wp_upgrade'))
								include_once(ABSPATH.'wp-admin/includes/upgrade.php');
							
							ob_clean();
							@wp_upgrade();
							@do_action('after_db_upgrade');
							ob_end_clean();
						}
					}
				}
				
				if(isset($params['secure'])){
					
					if($decrypted = $iwp_mmb_core->_secure_data($params['secure'])){
						$decrypted = maybe_unserialize($decrypted);
						if(is_array($decrypted)){
									
							foreach($decrypted as $key => $val){
								if(!is_numeric($key))
									$params[$key] = $val;							
													
							}
							unset($params['secure']);
						} else $params['secure'] = $decrypted;
					}
					elseif(isset($params['secure']['account_info'])){
						$params['account_info'] = $params['secure']['account_info'];
					}
				}
				
				if( !$iwp_mmb_core->register_action_params( $action, $params ) ){
					global $_iwp_mmb_plugin_actions;					
					$_iwp_mmb_plugin_actions[$action] = $params;
				}
				
			} else {
				iwp_mmb_response($auth['error'], false);
			}
		} else {
			IWP_MMB_Stats::set_hit_count();
		}
		ob_end_clean();
	}
}
/* Main response function */
if( !function_exists ( 'iwp_mmb_response' )) {

	function iwp_mmb_response($response = false, $success = true)
	{
		$return = array();
		
		if ((is_array($response) && empty($response)) || (!is_array($response) && strlen($response) == 0))
			$return['error'] = 'Empty response.';
		else if ($success)
			$return['success'] = $response;
		else
			$return['error'] = $response;
		
		if( !headers_sent() ){
			header('HTTP/1.0 200 OK');
			header('Content-Type: text/plain');
		}
		exit("<IWPHEADER>" . base64_encode(serialize($return))."<ENDIWPHEADER>");
	}
}



if( !function_exists ( 'iwp_mmb_add_site' )) {
	function iwp_mmb_add_site($params)
	{
		global $iwp_mmb_core;
		$num = extract($params);
		
		if ($num) {
			if (!get_option('iwp_client_action_message_id') && !get_option('iwp_client_public_key')) {
				$public_key = base64_decode($public_key);
				
				
				if(trim($activation_key) != get_option('iwp_client_activate_key')){ //iwp
					iwp_mmb_response('Invalid activation key', false);
					return;
				}
				
				if (checkOpenSSL() && !$user_random_key_signing) {
					$verify = openssl_verify($action . $id, base64_decode($signature), $public_key);
					if ($verify == 1) {
						$iwp_mmb_core->set_admin_panel_public_key($public_key);
						$iwp_mmb_core->set_client_message_id($id);
						$iwp_mmb_core->get_stats_instance();
						if(is_array($notifications) && !empty($notifications)){
							$iwp_mmb_core->stats_instance->set_notifications($notifications);
						}
						if(is_array($brand) && !empty($brand)){
							update_option('iwp_client_brand',$brand);
						}
						
						iwp_mmb_response($iwp_mmb_core->stats_instance->get_initial_stats(), true);
						delete_option('iwp_client_activate_key');//iwp
					} else if ($verify == 0) {
						iwp_mmb_response('Invalid message signature. Please contact us if you see this message often.', false);
					} else {
						iwp_mmb_response('Command not successful. Please try again.', false);
					}
				} else {
					if (!get_option('iwp_client_nossl_key')) {
						srand();
						
						$random_key = md5(base64_encode($public_key) . rand(0, getrandmax()));
						
						$iwp_mmb_core->set_random_signature($random_key);
						$iwp_mmb_core->set_client_message_id($id);
						$iwp_mmb_core->set_admin_panel_public_key($public_key);
						$iwp_mmb_core->get_stats_instance();						
						if(is_array($notifications) && !empty($notifications)){
							$iwp_mmb_core->stats_instance->set_notifications($notifications);
						}
						
						if(is_array($brand) && !empty($brand)){
							update_option('iwp_client_brand',$brand);
						}
						
						iwp_mmb_response($iwp_mmb_core->stats_instance->get_initial_stats(), true);
						delete_option('iwp_client_activate_key');//IWP
					} else
						iwp_mmb_response('Please deactivate & activate InfiniteWP Client plugin on your site, then add the site again.', false);
				}
			} else {
				iwp_mmb_response('Please deactivate &amp; activate InfiniteWP Client plugin on your site, then add the site again.', false);
			}
		} else {
			iwp_mmb_response('Invalid parameters received. Please try again.', false);
		}
	}
}

if( !function_exists ( 'iwp_mmb_remove_site' )) {
	function iwp_mmb_remove_site($params)
	{
		extract($params);
		global $iwp_mmb_core;
		$iwp_mmb_core->uninstall( $deactivate );
		
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		$plugin_slug = basename(dirname(__FILE__)) . '/' . basename(__FILE__);
		
		if ($deactivate) {
			deactivate_plugins($plugin_slug, true);
		}
		
		if (!is_plugin_active($plugin_slug))
			iwp_mmb_response(array(
				'deactivated' => 'Site removed successfully. <br /><br />InfiniteWP Client plugin successfully deactivated.'
			), true);
		else
			iwp_mmb_response(array(
				'removed_data' => 'Site removed successfully. <br /><br /><b>InfiniteWP Client plugin was not deactivated.</b>'
			), true);
		
	}
}
if( !function_exists ( 'iwp_mmb_stats_get' )) {
	function iwp_mmb_stats_get($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_stats_instance();
		iwp_mmb_response($iwp_mmb_core->stats_instance->get($params), true);
	}
}

if( !function_exists ( 'iwp_mmb_client_header' )) {
	function iwp_mmb_client_header()
	{	global $iwp_mmb_core, $current_user;
		
		if(!headers_sent()){
			if(isset($current_user->ID))
				$expiration = time() + apply_filters('auth_cookie_expiration', 10800, $current_user->ID, false);
			else 
				$expiration = time() + 10800;
				
			setcookie(IWP_MMB_XFRAME_COOKIE, md5(IWP_MMB_XFRAME_COOKIE), $expiration, COOKIEPATH, COOKIE_DOMAIN, false, true);
			$_COOKIE[IWP_MMB_XFRAME_COOKIE] = md5(IWP_MMB_XFRAME_COOKIE);
		}
	}
}

if( !function_exists ( 'iwp_mmb_pre_init_stats' )) {
	function iwp_mmb_pre_init_stats( $params )
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_stats_instance();
		return $iwp_mmb_core->stats_instance->pre_init_stats($params);
	}
}

if( !function_exists ( 'iwp_mmb_backup_now' )) {
//backup
	function iwp_mmb_backup_now($params)
	{
		global $iwp_mmb_core;
		
		$iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->backup($params);
		
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else {
			iwp_mmb_response($return, true);
		}
	}
}

if( !function_exists ( 'iwp_mmb_run_task_now' )) {
	function iwp_mmb_run_task_now($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->task_now($params['task_name']);
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else {
			iwp_mmb_response($return, true);
		}
	}
}

if( !function_exists ( 'iwp_mmb_delete_task_now' )) {
	function iwp_mmb_delete_task_now($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->delete_task_now($params['task_name']);
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else {
			iwp_mmb_response($return, true);
		}
	}
}
if( !function_exists ( 'iwp_mmb_check_backup_compat' )) {
	function iwp_mmb_check_backup_compat($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->check_backup_compat($params);
		
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else {
			iwp_mmb_response($return, true);
		}
	}
}

if( !function_exists ( 'iwp_mmb_get_backup_req' )) {
	function iwp_mmb_get_backup_req( $params )
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_stats_instance();
		$return = $iwp_mmb_core->stats_instance->get_backup_req($params);
		
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else {
		iwp_mmb_response($return, true);
		}
	}
}


if( !function_exists ( 'iwp_mmb_scheduled_backup' )) {
	function iwp_mmb_scheduled_backup($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->set_backup_task($params);
		iwp_mmb_response($return, $return);
	}
}


if( !function_exists ( 'mmm_delete_backup' )) {
	function mmm_delete_backup($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->delete_backup($params);
		iwp_mmb_response($return, $return);
	}
}

if( !function_exists ( 'iwp_mmb_optimize_tables' )) {
	function iwp_mmb_optimize_tables($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->optimize_tables();
		if ($return)
			iwp_mmb_response($return, true);
		else
			iwp_mmb_response(false, false);
	}
}
if( !function_exists ( 'iwp_mmb_restore_now' )) {
	function iwp_mmb_restore_now($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->restore($params);
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else
			iwp_mmb_response($return, true);
		
	}
}


if( !function_exists ( 'iwp_mmb_backup_repository' )) {
	function iwp_mmb_backup_repository($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_backup_repository_instance();
		$return = $iwp_mmb_core->backup_repository_instance->backup_repository($params);
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else
			iwp_mmb_response($return, true);
	}
}


if( !function_exists ( 'iwp_mmb_clean_orphan_backups' )) {
	function iwp_mmb_clean_orphan_backups()
	{
		global $iwp_mmb_core;
		$backup_instance = $iwp_mmb_core->get_backup_instance();
		$return = $iwp_mmb_core->backup_instance->cleanup();
		if(is_array($return))
			iwp_mmb_response($return, true);
		else
			iwp_mmb_response($return, false);
	}
}

if( !function_exists ( 'iwp_mmb_update_client_plugin' )) {
	function iwp_mmb_update_client_plugin($params)
	{
		global $iwp_mmb_core;
		iwp_mmb_response($iwp_mmb_core->update_client_plugin($params), true);
	}
}

if( !function_exists ( 'iwp_mmb_wp_checkversion' )) {
	function iwp_mmb_wp_checkversion($params)
	{
		include_once(ABSPATH . 'wp-includes/version.php');
		global $iwp_mmb_wp_version, $iwp_mmb_core;
		iwp_mmb_response($iwp_mmb_wp_version, true);
	}
}
if( !function_exists ( 'iwp_mmb_search_posts_by_term' )) {
	function iwp_mmb_search_posts_by_term($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_search_instance();
		
		$search_type = trim($params['search_type']);
		$search_term = strtolower(trim($params['search_term']));

		switch ($search_type){		
			case 'plugin':
				$plugins = get_option('active_plugins');
				
				$have_plugin = false;
				foreach ($plugins as $plugin) {
					if(strpos($plugin, $search_term)>-1){
						$have_plugin = true;
					}
				}
				if($have_plugin){
					iwp_mmb_response(serialize($plugin), true);
				}else{
					iwp_mmb_response(false, false);
				}
				break;
			case 'theme':
				$theme = strtolower(get_option('template'));
				if(strpos($theme, $search_term)>-1){
					iwp_mmb_response($theme, true);
				}else{
					iwp_mmb_response(false, false);
				}
				break;
			default: iwp_mmb_response(false, false);		
		}
		$return = $iwp_mmb_core->search_instance->iwp_mmb_search_posts_by_term($params);
		
		
		
		if ($return_if_true) {
			iwp_mmb_response($return_value, true);
		} else {
			iwp_mmb_response($return_if_false, false);
		}
	}
}

if( !function_exists ( 'iwp_mmb_install_addon' )) {
	function iwp_mmb_install_addon($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_installer_instance();
		$return = $iwp_mmb_core->installer_instance->install_remote_file($params);
		iwp_mmb_response($return, true);
		
	}
}

if( !function_exists ( 'iwp_mmb_do_upgrade' )) {
	function iwp_mmb_do_upgrade($params)
	{
		global $iwp_mmb_core, $iwp_mmb_upgrading;
		$iwp_mmb_core->get_installer_instance();
		$return = $iwp_mmb_core->installer_instance->do_upgrade($params);
		iwp_mmb_response($return, true);
		
	}
}

if( !function_exists ( 'iwp_mmb_add_user' )) {
	function iwp_mmb_add_user($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_user_instance();
			$return = $iwp_mmb_core->user_instance->add_user($params);
		if (is_array($return) && array_key_exists('error', $return))
		
			iwp_mmb_response($return['error'], false);
		else {
			iwp_mmb_response($return, true);
		}
		
	}
}

if( !function_exists ('iwp_mmb_get_users')) {
	function iwp_mmb_get_users($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_user_instance();
			$return = $iwp_mmb_core->user_instance->get_users($params);
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else {
			iwp_mmb_response($return, true);
		}
	}
}

if( !function_exists ('iwp_mmb_edit_users')) {
	function iwp_mmb_edit_users($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_user_instance();
		$return = $iwp_mmb_core->user_instance->edit_users($params);
		iwp_mmb_response($return, true);
	}
}

if( !function_exists ( 'iwp_mmb_iframe_plugins_fix' )) {
	function iwp_mmb_iframe_plugins_fix($update_actions)
	{
		foreach($update_actions as $key => $action)
		{
			$update_actions[$key] = str_replace('target="_parent"','',$action);
		}
		
		return $update_actions;
		
	}
}

if( !function_exists ( 'iwp_mmb_set_notifications' )) {
	function iwp_mmb_set_notifications($params)
	{
		global $iwp_mmb_core;
		$iwp_mmb_core->get_stats_instance();
			$return = $iwp_mmb_core->stats_instance->set_notifications($params);
		if (is_array($return) && array_key_exists('error', $return))
			iwp_mmb_response($return['error'], false);
		else {
			iwp_mmb_response($return, true);
		}
		
	}
}

if( !function_exists ( 'iwp_mmb_set_alerts' )) {
	function iwp_mmb_set_alerts($params)
	{
		global $iwp_mmb_core;
			$iwp_mmb_core->get_stats_instance();
			$return = $iwp_mmb_core->stats_instance->set_alerts($params);
			iwp_mmb_response(true, true);
	}		
}

/*
if(!function_exists('iwp_mmb_more_reccurences')){
	//Backup Tasks
	add_filter('cron_schedules', 'iwp_mmb_more_reccurences');
	function iwp_mmb_more_reccurences($schedules) {
		$schedules['halfminute'] = array('interval' => 30, 'display' => 'Once in a half minute');
		$schedules['minutely'] = array('interval' => 60, 'display' => 'Once in a minute');
		$schedules['fiveminutes'] = array('interval' => 300, 'display' => 'Once every five minutes');
		$schedules['tenminutes'] = array('interval' => 600, 'display' => 'Once every ten minutes');
		
		return $schedules;
	}
}
	
	add_action('iwp_client_backup_tasks', 'iwp_client_check_backup_tasks');

if( !function_exists('iwp_client_check_backup_tasks') ){
 	function iwp_client_check_backup_tasks() {
		global $iwp_mmb_core, $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;
		
		$iwp_mmb_core->get_backup_instance();
		$iwp_mmb_core->backup_instance->check_backup_tasks();
	}
}
*/
	
if( !function_exists('iwp_check_notifications') ){
 	function iwp_check_notifications() {
		global $iwp_mmb_core, $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;
		
		$iwp_mmb_core->get_stats_instance();
		$iwp_mmb_core->stats_instance->check_notifications();
	}
}


if( !function_exists('iwp_mmb_get_plugins_themes') ){
 	function iwp_mmb_get_plugins_themes($params) {
		global $iwp_mmb_core;
		$iwp_mmb_core->get_installer_instance();
		$return = $iwp_mmb_core->installer_instance->get($params);
		iwp_mmb_response($return, true);
	}
}

if( !function_exists('iwp_mmb_edit_plugins_themes') ){
 	function iwp_mmb_edit_plugins_themes($params) {
		global $iwp_mmb_core;
		$iwp_mmb_core->get_installer_instance();
		$return = $iwp_mmb_core->installer_instance->edit($params);
		iwp_mmb_response($return, true);
	}
}

if( !function_exists('iwp_mmb_maintenance_mode')){
 	function iwp_mmb_maintenance_mode( $params ) {
		global $wp_object_cache;
		
		$default = get_option('iwp_client_maintenace_mode');
		$params = empty($default) ? $params : array_merge($default, $params);
		update_option("iwp_client_maintenace_mode", $params);
		
		if(!empty($wp_object_cache))
			@$wp_object_cache->flush(); 
		iwp_mmb_response(true, true);
	}
}

if( !function_exists('iwp_mmb_plugin_actions') ){
 	function iwp_mmb_plugin_actions() {
		global $iwp_mmb_actions, $iwp_mmb_core;
		
		if(!empty($iwp_mmb_actions)){
			global $_iwp_mmb_plugin_actions;
			if(!empty($_iwp_mmb_plugin_actions)){
				$failed = array();
				foreach($_iwp_mmb_plugin_actions as $action => $params){
					if(isset($iwp_mmb_actions[$action]))
						call_user_func($iwp_mmb_actions[$action], $params);
					else 
						$failed[] = $action;
				}
				if(!empty($failed)){
					$f = implode(', ', $failed);
					$s = count($f) > 1 ? 'Actions "' . $f . '" do' : 'Action "' . $f . '" does';
					iwp_mmb_response($s.' not exist. Please update your IWP Client plugin.', false);
				}
					
			}
		}
		
		global $pagenow, $current_user, $mmode;
		if( !is_admin() && !in_array($pagenow, array( 'wp-login.php' ))){
			$mmode = get_option('iwp_client_maintenace_mode');
			if( !empty($mmode) ){
				if(isset($mmode['active']) && $mmode['active'] == true){
					if(isset($current_user->data) && !empty($current_user->data) && isset($mmode['hidecaps']) && !empty($mmode['hidecaps'])){
						$usercaps = array();
						if(isset($current_user->caps) && !empty($current_user->caps)){
							$usercaps = $current_user->caps;
						}
						foreach($mmode['hidecaps'] as $cap => $hide){
							if(!$hide)
								continue;
							
							foreach($usercaps as $ucap => $val){
								if($ucap == $cap){
									ob_end_clean();
									ob_end_flush();
									die($mmode['template']);
								}
							}
						}
					} else
						die($mmode['template']);
				}
			}
		}
	}
} 

if( !function_exists ( 'iwp_mmb_execute_php_code' )) {
	function iwp_mmb_execute_php_code($params)
	{ 		
		ob_start();
		eval($params['code']);
		$return = ob_get_flush();
		iwp_mmb_response(print_r($return, true), true);
	}
}

if( !function_exists('iwp_mmb_client_brand')){
 	function iwp_mmb_client_brand($params) {
		update_option("iwp_client_brand",$params['brand']);
		iwp_mmb_response(true, true);
	}
}


if(!function_exists('checkOpenSSL')){
	function checkOpenSSL(){
	if(!function_exists('openssl_verify')){
		return false;
	}
	else{
		$key = @openssl_pkey_new();
		@openssl_pkey_export($key, $privateKey);
		$privateKey	= base64_encode($privateKey);
		$publicKey = @openssl_pkey_get_details($key);
		$publicKey 	= $publicKey["key"];
		
		if(empty($publicKey) || empty($privateKey)){
			return false;
		}
	}
	return true;
  }
}

$iwp_mmb_core = new IWP_MMB_Core();

if(isset($_GET['auto_login']))
	$iwp_mmb_core->automatic_login();	

if (function_exists('register_activation_hook'))
    register_activation_hook( __FILE__ , array( $iwp_mmb_core, 'install' ));

if (function_exists('register_deactivation_hook'))
    register_deactivation_hook(__FILE__, array( $iwp_mmb_core, 'uninstall' ));

if (function_exists('add_action'))
	add_action('init', 'iwp_mmb_plugin_actions', 99999);

if (function_exists('add_filter'))
	add_filter('install_plugin_complete_actions','iwp_mmb_iframe_plugins_fix');
	
if(	isset($_COOKIE[IWP_MMB_XFRAME_COOKIE]) ){
	remove_action( 'admin_init', 'send_frame_options_header');
	remove_action( 'login_init', 'send_frame_options_header');
}

?>