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
	/*
	function ftp_backup($args)
    {
        extract($args);
        //Args: $ftp_username, $ftp_password, $ftp_hostname, $backup_file, $ftp_remote_folder, $ftp_site_folder
        $port = $ftp_port ? $ftp_port : 21; //default port is 21
        if ($ftp_ssl) {
            if (function_exists('ftp_ssl_connect')) {
                $conn_id = ftp_ssl_connect($ftp_hostname,$port);
            } else {
                return array(
                    'error' => 'Your server doesn\'t support SFTP',
                    'partial' => 1
                );
            }
        } else {
            if (function_exists('ftp_connect')) {
                $conn_id = ftp_connect($ftp_hostname,$port);
                if ($conn_id === false) {
                    return array(
                        'error' => 'Failed to connect to ' . $ftp_hostname,
                        'partial' => 1
                    );
                }
            } else {
                return array(
                    'error' => 'Your server doesn\'t support FTP',
                    'partial' => 1
                );
            }
        }
        $login = @ftp_login($conn_id, $ftp_username, $ftp_password);
        if ($login === false) {
            return array(
                'error' => 'FTP login failed for ' . $ftp_username . ', ' . $ftp_password,
                'partial' => 1
            );
        }
        
        if($ftp_passive){
					@ftp_pasv($conn_id,true);
				}
				
        @ftp_mkdir($conn_id, $ftp_remote_folder);
        if ($ftp_site_folder) {
            $ftp_remote_folder .= '/' . $this->site_name;
        }
        @ftp_mkdir($conn_id, $ftp_remote_folder);
        
        $upload = @ftp_put($conn_id, $ftp_remote_folder . '/' . basename($backup_file), $backup_file, FTP_BINARY);
        
        if ($upload === false) { //Try ascii
            $upload = @ftp_put($conn_id, $ftp_remote_folder . '/' . basename($backup_file), $backup_file, FTP_ASCII);
        }
        ftp_close($conn_id);
        
        if ($upload === false) {
            return array(
                'error' => 'Failed to upload file to FTP. Please check your specified path.',
                'partial' => 1
            );
        }
        
        return true;
    }
    
    function remove_ftp_backup($args)
    {
        extract($args);
        //Args: $ftp_username, $ftp_password, $ftp_hostname, $backup_file, $ftp_remote_folder
        $port = $ftp_port ? $ftp_port : 21; //default port is 21
        if ($ftp_ssl && function_exists('ftp_ssl_connect')) {
            $conn_id = ftp_ssl_connect($ftp_hostname,$port);
        } else if (function_exists('ftp_connect')) {
            $conn_id = ftp_connect($ftp_hostname,$port);
        }
        
        if ($conn_id) {
            $login = @ftp_login($conn_id, $ftp_username, $ftp_password);
            if ($ftp_site_folder)
                $ftp_remote_folder .= '/' . $this->site_name;
            
            if($ftp_passive){
							@ftp_pasv($conn_id,true);
						}
						
            $delete = ftp_delete($conn_id, $ftp_remote_folder . '/' . $backup_file);
            
            ftp_close($conn_id);
        }
        
    }
    
    function get_ftp_backup($args)
    {
        extract($args);
        //Args: $ftp_username, $ftp_password, $ftp_hostname, $backup_file, $ftp_remote_folder
        $port = $ftp_port ? $ftp_port : 21; //default port is 21
        if ($ftp_ssl && function_exists('ftp_ssl_connect')) {
            $conn_id = ftp_ssl_connect($ftp_hostname,$port);
            
        } else if (function_exists('ftp_connect')) {
            $conn_id = ftp_connect($ftp_hostname,$port);
            if ($conn_id === false) {
                return false;
            }
        } 
        $login = @ftp_login($conn_id, $ftp_username, $ftp_password);
        if ($login === false) {
            return false;
        } else {
        }
        
        if ($ftp_site_folder)
            $ftp_remote_folder .= '/' . $this->site_name;
        
        if($ftp_passive){
					@ftp_pasv($conn_id,true);
				}
        
        $temp = ABSPATH . 'iwp_temp_backup.zip';
        $get  = ftp_get($conn_id, $temp, $ftp_remote_folder . '/' . $backup_file, FTP_BINARY);
        if ($get === false) {
            return false;
        } else {
			
        }
        ftp_close($conn_id);
        
        return $temp;
    }
	
	 function amazons3_backup($args)
    {
        if ($this->iwp_mmb_function_exists('curl_init')) {
            require_once($iwp_mmb_plugin_dir.'/lib/s3.php');
            extract($args);
            
            if ($as3_site_folder == true)
                $as3_directory .= '/' . $this->site_name;
            
            $endpoint = isset($as3_bucket_region) ? $as3_bucket_region : 's3.amazonaws.com';
            
            $s3 = new S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, $endpoint);
            
            $s3->putBucket($as3_bucket, S3::ACL_PUBLIC_READ);
            
            if ($s3->putObjectFile($backup_file, $as3_bucket, $as3_directory . '/' . basename($backup_file), S3::ACL_PRIVATE)) {
                return true;
            } else {
                return array(
                    'error' => 'Failed to upload to Amazon S3. Please check your details and set upload/delete permissions on your bucket.',
                    'partial' => 1
                );
            }
        } else {
            return array(
                'error' => 'You cannot use Amazon S3 on your server. Please enable curl first.',
                'partial' => 1
            );
        }
    }
    
    function remove_amazons3_backup($args)
    {
        require_once($iwp_mmb_plugin_dir.'/lib/s3.php');
        extract($args);
        if ($as3_site_folder == true)
            $as3_directory .= '/' . $this->site_name;
        $endpoint = isset($as3_bucket_region) ? $as3_bucket_region : 's3.amazonaws.com';
        $s3       = new S3($as3_access_key, str_replace(' ', '+', $as3_secure_key), false, $endpoint);
        $s3->deleteObject($as3_bucket, $as3_directory . '/' . $backup_file);
    }
    
    function get_amazons3_backup($args)
    {
        require_once($iwp_mmb_plugin_dir.'/lib/s3.php');
        extract($args);
        $endpoint = isset($as3_bucket_region) ? $as3_bucket_region : 's3.amazonaws.com';
        $s3       = new S3($as3_access_key, str_replace(' ', '+', $as3_secure_key), false, $endpoint);
        if ($as3_site_folder == true)
            $as3_directory .= '/' . $this->site_name;
        
        $temp = ABSPATH . 'iwp_temp_backup.zip';
        $s3->getObject($as3_bucket, $as3_directory . '/' . $backup_file, $temp);
        
        return $temp;
    }
	
	   function dropbox_backup($args)
    {
        
        extract($args);
        
        if(isset($consumer_secret) && !empty($consumer_secret)){
        	//New way
        	require_once($iwp_mmb_plugin_dir.'/lib/dropbox.oauth.php');
   
					$dropbox = new Dropbox($consumer_key, $consumer_secret);	
					$dropbox->setOAuthToken($oauth_token);
					$dropbox->setOAuthTokenSecret($oauth_token_secret);
        	
        	if ($dropbox_site_folder == true)
            $dropbox_destination .= '/' . $this->site_name;
          
          try{
          
          	$dropbox->filesPost($dropbox_destination, $backup_file,true);
          	
          } catch(Exception $e){
          	return array(
                'error' => 'Dropbox upload error. '.$e->getMessage()
            );
          }
          
          return true;
        	
        } else {
        	//old way
        require_once($iwp_mmb_plugin_dir.'/lib/dropbox.php');
       // extract($args);
        
        //$email, $password, $backup_file, $destination, $dropbox_site_folder
        
        $size = ceil(filesize($backup_file) / 1024);
        if ($size > 300000) {
            return array(
                'error' => 'Cannot upload file to Dropbox. Dropbox has upload limit of 300Mb per file.',
                'partial' => 1
            );
        }
        
        if ($dropbox_site_folder == true)
            $dropbox_destination .= '/' . $this->site_name;
        
        try {
            $uploader = new DropboxUploader($dropbox_username, $dropbox_password);
            $uploader->upload($backup_file, $dropbox_destination);
        }
        catch (Exception $e) {
            return array(
                'error' => $e->getMessage(),
                'partial' => 1
            );
        }
        
        return true;
      }
        
    }
    
    function remove_dropbox_backup($args){
    	 extract($args);
        if(isset($consumer_secret) && !empty($consumer_secret)){
        	//New way
        	require_once($iwp_mmb_plugin_dir.'/lib/dropbox.oauth.php');
   
					$dropbox = new Dropbox($consumer_key, $consumer_secret);	
					$dropbox->setOAuthToken($oauth_token);
					$dropbox->setOAuthTokenSecret($oauth_token_secret);
        	
        	if ($dropbox_site_folder == true)
            $dropbox_destination .= '/' . $this->site_name;
          
          try{
          	$dropbox->fileopsDelete($dropbox_destination.'/'.$backup_file, true);
          } catch(Exception $e){
          	
          }
    }
  }
  
  function get_dropbox_backup($args){
  	extract($args);
  	
        if(isset($consumer_secret) && !empty($consumer_secret)){
        	//New way
        	require_once($iwp_mmb_plugin_dir.'/lib/dropbox.oauth.php');
   
					$dropbox = new Dropbox($consumer_key, $consumer_secret);	
					$dropbox->setOAuthToken($oauth_token);
					$dropbox->setOAuthTokenSecret($oauth_token_secret);
        	
        	if ($dropbox_site_folder == true)
            $dropbox_destination .= '/' . $this->site_name;
          
          $temp = ABSPATH . 'iwp_temp_backup.zip';
          
          try{
          	$file = $dropbox->filesGet($dropbox_destination.'/'.$backup_file, true);
          	
          	if(isset($file['data']) && !empty($file['data']) )
          		$stream = base64_decode($file['data']); 
          		else 
          			return false;
         
          $handle = @fopen($temp, 'w+');
          $result = fwrite($handle,$stream);
          fclose($handle);
          
          if($result)
          	return $temp;
          else
          	return false;
          
          } catch(Exception $e){
          	
          	
          	return false;
          }
    
    } else {
    	return false;
    }        
    }
	
	function email_backup($args)
    {
        $email = $args['email'];
        
        if (!is_email($email)) {
            return array(
                'error' => 'Your email (' . $email . ') is not correct'
            );
        }
        $backup_file = $args['file_path'];
        $task_name   = isset($args['task_name']) ? $args['task_name'] : '';
        if (file_exists($backup_file) && $email) {
            $attachments = array(
                $backup_file
            );
            $headers     = 'From: InfiniteWP <no-reply@infinitewp.com>' . "\r\n";
            $subject     = "InfiniteWP - " . $task_name . " - " . $this->site_name;
            ob_start();
            $result = wp_mail($email, $subject, $subject, $headers, $attachments);
            ob_end_clean();
            
        }
        
        if (!$result) {
            return array(
                'error' => 'Email not sent. Maybe your backup is too big for email or email server is not available on your website.'
            );
        }
        return true;
        
    }
	
	function update_tasks($tasks)
    {
        $this->tasks = $tasks;
        update_option('iwp_client_backup_tasks', $tasks);
    }*/
	
}
?>