<?php
/*
Plugin Name: WordPress Export
Plugin URI: http://blog.usf.edu
Description: Export WordPress data to multiple formats
Version: 0.3
Author: Eric Pierce
Author URI: http://epierce.blog.usf.edu
*/

session_start();
header("Cache-control: private"); // IE 6 Fix. 

require(dirname(__FILE__) . '/../../../wp-config.php');
global $wpdb;

//Array of export filetypes
$filetypes = array('MT', 'SQL', 'blogger');

if (isset($_REQUEST['export_filetype']))
                $cur_filetype = $_REQUEST['export_filetype'];
        else
                $cur_filetype = "MT";

add_action('admin_menu', 'WPexport_admin_menu');

        /*
         * Add an options pane for this plugin.
         */
	
           function WPexport_admin_menu() {
               add_options_page('Export', 'Export', 10, __FILE__,'WPexport_option_page');
           }

	  function WPexport_functions() {
		global $filetypes;
		foreach ($filetypes as $filetype) {
			require_once($filetype.".php");
		} 
	   }

	   function WPexport_menu() {
                global $filetypes;
                global $cur_filetype;
		echo '<ul id="export_menu">';
		foreach ($filetypes as $filetype) {
			$url = $_SERVER['SCRIPT_NAME'] . "?page=" . $_REQUEST['page'] . "&export_filetype=";
			$function = "$filetype"."_title();";
			if ($filetype == $cur_filetype) {
			   echo "<li class=\"current\">";
			   eval($function);
			   echo "</li>";
			} else {
			   echo "<li><a href=\"$url$filetype\">";
			   eval($function);
			   echo "</a></li>";
			}

		}
	     echo "</ul>";
           }

	function WPexport_short_desc() {
                global $cur_filetype;;
		$function = "$cur_filetype"."_short_desc();";
		eval($function);
	}

        function WPexport_export_display() {
                global $cur_filetype;;
                $function = "$cur_filetype"."_export_display();";
                eval($function);
        }

           function WPexport_option_page() {

		WPexport_functions();
		include('admin.css');
		 WPexport_menu();
		?>

		<div class="wrap export_first" id="export_pane">
		   <h2>Export Data</h2>
   		   <center>
    			<fieldset class="options">
      			   <label for="WPexport"><?php WPexport_short_desc() ?></label><br>
        			<?php WPexport_export_display(); ?>

    			</fieldset>
   		    </center>
		</div>
	<?php
           }
?>
