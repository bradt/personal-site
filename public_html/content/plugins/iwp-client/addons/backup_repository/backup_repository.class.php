<?php 
class IWP_MMB_Backup_Repository extends IWP_MMB_Backup
{
	/*var $site_name;
    var $statuses;
    var $tasks;
    var $s3;
    var $ftp;
    var $dropbox;
	
    function __construct()
    {
        parent::__construct();
        $this->site_name = str_replace(array(
            "_",
            "/"
        ), array(
            "",
            "-"
        ), rtrim($this->remove_http(get_bloginfo('url')), "/"));
        $this->statuses  = array(
            'db_dump' => 1,
            'db_zip' => 2,
            'files_zip' => 3,
			's3' => 4,
            'dropbox' => 5,
            'ftp' => 6,
            'email' => 7,
            'finished' => 100
        );
		
        $this->tasks = get_option('iwp_client_backup_tasks');
    }*/
    
	function backup_repository($args){
		
				
        if (!empty($args))
            extract($args);
        
        $tasks = $this->tasks;
        $task  = $tasks['Backup Now'];
        
		@ini_set('memory_limit', '256M');
        @set_time_limit(1200);
		
        if (!empty($task)) {
            extract($task['task_args']);
        }
        
        $results = $task['task_results'];
	
        if (is_array($results) && count($results)) {
            $backup_file = $results[count($results) - 1]['server']['file_path'];
        }
		
        
        if ($backup_file && file_exists($backup_file)) {
            //FTP, Amazon S3 or Dropbox
            if (isset($account_info['iwp_ftp']) && !empty($account_info)) {
                $account_info['iwp_ftp']['backup_file'] = $backup_file;
                $return                                 = $this->ftp_backup($account_info['iwp_ftp']);
            }
            
            if (isset($account_info['iwp_amazon_s3']) && !empty($account_info['iwp_amazon_s3'])) {
                $account_info['iwp_amazon_s3']['backup_file'] = $backup_file;
                $return                                       = $this->amazons3_backup($account_info['iwp_amazon_s3']);
            }
            
            if (isset($account_info['iwp_dropbox']) && !empty($account_info['iwp_dropbox'])) {
                $account_info['iwp_dropbox']['backup_file'] = $backup_file;
                $return                                     = $this->dropbox_backup($account_info['iwp_dropbox']);
            }
            
            if (isset($account_info['iwp_email']) && !empty($account_info['iwp_email'])) {
                $account_info['iwp_email']['file_path'] = $backup_file;
                $account_info['iwp_email']['task_name'] = 'Backup Now';
                $return                                 = $this->email_backup($account_info['iwp_email']);
            }
            
            
            if ($return == true && $del_host_file) {
                @unlink($backup_file);
                unset($tasks['Backup Now']['task_results'][count($results) - 1]['server']);
				$this->wpdb_reconnect();
                $this->update_tasks($tasks);
                //update_option('iwp_client_backup_tasks', $tasks);
            }
                        
        } else {
            $return = array(
                'error' => 'Backup file not found on your server. Please try again.'
            );
        }
        
        return $return;
        
	}
	
	
}
?>