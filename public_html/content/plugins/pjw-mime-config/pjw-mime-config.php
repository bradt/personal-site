<?php
/*
 Plugin Name: PJW Mime Config
 Plugin URI: http://blog.ftwr.co.uk/wordpress/mime-config/
 Description: Allows you to <a href="options-general.php?page=pjw-mime-config">extend the list of mime-types</a> supported by the builtin uploader.
 Author: Peter Westwood
 Version: 1.00
 Author URI: http://blog.ftwr.co.uk/
 */

/*  Copyright 2006-9  Peter Westwood  (email : peter.westwood@ftwr.co.uk)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class pjw_mime_config
{
	// Some default extras
	var	$mime_types = array ();
	var $default_types = array (
						'ac3' => 'audio/ac3',
						'mpa' => 'audio/MPA',
						'flv' => 'video/x-flv'
						);

	var $message = "";
	var $message_class = "";

	//Constructor
	function pjw_mime_config() {
		add_action('admin_menu', array(&$this,'admin_menu'));
		add_filter('upload_mimes',array(&$this,'upload_mimes'));
		add_option('pjw_mime_types',$this->default_types,'Additional mime-types for the inline uploader.','no');
		$this->mime_types = get_option('pjw_mime_types');
	}

	function admin_menu() {
		add_options_page('Mime types','Mime types','manage_options','pjw-mime-config',array(&$this,'display_page'));
		if ((isset($_GET["page"]) && "pjw-mime-config" == $_GET["page"]) || ( isset($_POST["page"]) && "pjw-mime-config" == $_POST["page"]))
		$this->handle_actions();
	}

	function handle_actions() {
		if (!current_user_can('manage_options'))
		{
			$this->message ='Sorry you don\'t have the privileges to do this!';
			$this->message_class = 'error';
		}
		else
		{
			if (isset($_POST["action"]) && "addmime" == $_POST["action"])
			{
				check_admin_referer('add-mime');
				$this->mime_types[$_POST["mime-ext"]] = $_POST["mime-type"];
				update_option('pjw_mime_types',$this->mime_types);
				$this->message = $_POST["mime-ext"] . " mime-type added.";
				$this->message_class = 'updated';
			}
			elseif (isset($_POST["action"]) && "restoremime" == $_POST["action"])
			{
				check_admin_referer('restore-mimes');
				update_option('pjw_mime_types',$this->default_types);
				$this->mime_types = get_option('pjw_mime_types');
				$this->message = 'Restored default mime-types.';
				$this->message_class = 'updated';
			}
			elseif (isset($_POST["action"]) && "addmimefromfile" == $_POST["action"])
			{
				check_admin_referer('add-mime-from-file');
				if (isset($_FILES['userfile']))
				{
					if (isset($_FILES['userfile']['error']))
					{
						//Check Error Codes
						switch ($_FILES['userfile']['error'])
						{
							case UPLOAD_ERR_INI_SIZE:
								$message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
								break;
							case UPLOAD_ERR_FORM_SIZE:
								$message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
								break;
							case UPLOAD_ERR_PARTIAL:
								$message = 'The uploaded file was only partially uploaded.';
								break;
							case UPLOAD_ERR_NO_FILE:
								$message = 'No file was uploaded.';
								break;
							case UPLOAD_ERR_NO_TMP_DIR:
								$message = 'Missing a temporary folder.';
								break;
							case UPLOAD_ERR_CANT_WRITE:
								$message = 'Can\'t write to disk.';
								break;
						}

					}

					if (!is_uploaded_file($_FILES['userfile']['tmp_name']))
					{
						$message = 'Stop trying to spoof the $_FILES array hacker!';
					} else {
						$mime_file_lines = file($_FILES['userfile']['tmp_name']);
						if (!$mime_file_lines)
						{
							$message = 'Invalid file format';
						}
					}

					if (isset($message))
					{
						$this->message = 'Error: ' . $message;
						$this->message_class = 'error';
					} else {
						// Add the mime-types
						$counter = 0;
						foreach ($mime_file_lines as $line)
						{
							$mime_type = explode(" ",rtrim(rtrim($line,"\n"),"\r"));//Catch all sorts of line endings - CR/CRLF/LF
							$this->mime_types[$mime_type[1]] = $mime_type[0];
							$counter++;
						}
						update_option('pjw_mime_types',$this->mime_types);

						$this->message = "Added $counter mime-type(s)";
						$this->message_class = 'updated';
					}
				}
			}
			elseif (isset($_GET["action"]) && "delmime" == $_GET["action"])
			{
				check_admin_referer('delete-mime_'.$_GET["mime-ext"]);
				unset($this->mime_types[$_GET["mime-ext"]]);
				update_option('pjw_mime_types',$this->mime_types);
				$this->message = "The mime-type for the " . $_GET["mime-ext"] . " file extension was deleted.";
				$this->message_class = 'updated';
			}
		}
	}

	function display_page()
	{
		if (!current_user_can('manage_options'))
		{
			echo '<div id="message" class="updated fade"> <p>Sorry you don\'t have the privileges to do this!</p></div>';
		}
		else
		{
			if (strlen($this->message) > 0)
			{
				?>
					<div id="message" class="<?php echo $this->message_class; ?> fade">
						<p><?php echo $this->message; ?></p>
					</div>
				<?php
			}
			
			?>
				<div class="wrap">
				<?php screen_icon(); ?>
				<h2>Mime-type management</h2>
			<?php
			if (!empty($this->mime_types))
			{
				?>
					<h3>Registered mime-types</h3>
					<p>The following extra mime-types are currently registered for use with the inline uploader.</p>
					<table id="mime-types" width="100%" cellpadding="3" cellspacing="3">
						<tr>
						<th scope="col">File extension</th>
						<th scope="col">Mime type</th>
						<th scope="col">Action</th>
						</tr>
						<?php
							foreach($this->mime_types as $ext => $type)
							{
								$url = 'options-general.php?page=pjw-mime-config&amp;action=delmime&amp;mime-ext='.$ext;
								$url = wp_nonce_url($url,'delete-mime_'.$ext);
								?>
									<tr class='alternate'>
									<td align='center'><?php echo $ext; ?></td>
									<td align='center'><?php echo $type; ?></td>
									<td align='center'><a href="<?php echo $url;?>" class='delete'>Delete</a></td>
									</tr>
								<?php
							}
						?>
					</table>
				<?php
			}
			else
			{
				?>
					<p>No mime-types are currently registered. If you want you can restore the original set of extra mime-types</p>
					<form name="restoremime" id="restoremime" action="options-general.php?page=pjw-mime-config" method="post">
						<?php wp_nonce_field('restore-mimes'); ?>
						<p class="submit">
							<input type="hidden" name="action" value="restoremime" />
							<input type="submit" name="submit" value="Restore default mime-types &raquo;" class="button-primary" />
						</p>
					</form>
				<?php
			}
			?>
					<h3>Add New mime-type</h3>
					<form name="addmime" id="addmime" action="options-general.php?page=pjw-mime-config" method="post">
						<?php wp_nonce_field('add-mime'); ?>
						<p>File Extension: <input type="text" name="mime-ext" value="" /></p>
						<p>Associated mime-type: <input type="text" name="mime-type" value="" /></p>
						<p class="submit">
							<input type="hidden" name="action" value="addmime" />
							<input type="submit" name="submit" value="Add mime-type &raquo;" class="button-primary" />
						</p>
					</form>
					<h3>Add mime-types from file</h3>
					<p>You can upload a file containing a list of mime-types in the format &quot;mime/type extension&quot; like this:</p>
					<blockquote>
						<pre>
	audio/ac3 ac3
	audio/MPA mpa
	video/x-flv flv
						</pre>
					</blockquote>
					<form enctype="multipart/form-data" action="options-general.php?page=pjw-mime-config" method="post">
						<?php wp_nonce_field('add-mime-from-file'); ?>
						<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
						Select a file containing a list of mime types in the correct format: <input name="userfile" type="file" />
						<p class="submit">
							<input type="hidden" name="action" value="addmimefromfile" />
							<input type="submit" value="Add from file &raquo;" class="button-primary" />
						</p>
					</form>
				</div>
			<?php
		}
	}

	//Add in our extra mime-types to the supplied array
	function upload_mimes($mimes)
	{
		$this->mime_types = get_option('pjw_mime_types');
		return array_merge($mimes,$this->mime_types);
	}
}

/* Initialise outselves lambda stylee */
add_action('plugins_loaded', create_function('','global $pjw_mime_config; $pjw_mime_config = new pjw_mime_config;'));