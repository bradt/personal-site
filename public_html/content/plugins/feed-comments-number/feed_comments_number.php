<?php
/*
Plugin Name: Feed Comments Number
Plugin URI: http://josh.thespiffylife.com/wp-plugin-feed-comments-number-193/
Description: Adds an image to the bottom of your feed items showing the number of comments on them, linking back to the comments section of your post.
Version: 0.2.1
Author: Joshua Clayton
Author URI: http://josh.thespiffylife.com/
*/

if(!class_exists('feed_comments_number')) {
class feed_comments_number	{

	var $optionName = 'feed-comments-number';
	var $defaultOptions = array(
							'background'=>'FFFFFF',
							'text'=>'0000FF',
							'font'=>'Arial.ttf',
							'no_comments_format'=>'Add a Comment',
							'num_comments_format'=>'Comments (%d)',
							'the_content'=>true,
							'the_excerpt'=>false
						);
	var $options = array();
	var $url = '';
	var $message = '';

	// for php 4
	function feed_comments_number(){$this->__construct();}

	// for php 5
	function __construct() {
	
		$this->options = $this->getOptions();

		add_action('admin_menu', array(&$this,'add_admin_pages'));
		
		if($this->options['the_content'] === true) {
			add_filter('the_content', array(&$this,'the_content_intercept'));
			add_filter('the_content_rss', array(&$this,'the_content_intercept'));
		}
		if($this->options['the_excerpt'] === true) {
			add_filter('the_excerpt_rss', array(&$this,'the_content_intercept'));
		}
		
		if(isset($_POST['submit-fcn-options'])) {
			$this->updateOptions(array(
							'background'=>$_POST['background'], 
							'text'=>$_POST['text'],
							'font'=>$_POST['font'],
							'no_comments_format'=>$_POST['no_comments_format'],
							'num_comments_format'=>$_POST['num_comments_format'],
							'the_content'=>isset($_POST['the_content']),
							'the_excerpt'=>isset($_POST['the_excerpt'])
						));
			$this->options = $this->getOptions();
		}
						
		if(isset($_FILES['font'])) {
			$this->uploadFont();
		}

		$this->url = get_bloginfo('url').'/wp-content/plugins/feed-comments-number';

	}
		
	// Retrieves the options from the database
	// @return array
	function getOptions() {
		$options = get_option($this->optionName);
		$options = $this->checkOptions($options, true);
		return $options;
	}
	
	// checks the formatting of the options to see if they are valid and resorts to defaults if they aren't
	// @return array
	function checkOptions($options, $update_on_error=false) {
	
		$error = false;
		
		if(is_array($options)) {
		
			// if the colors aren't in 6 digit hex format, resort to defaults
			if(!isset($options['background']) || !preg_match('/[A-Z0-9]{6}/i', $options['background'])) {
				$options['background'] = $this->defaultOptions['background'];
				$error = true;
			}
			if(!isset($options['text']) || !preg_match('/[A-Z0-9]{6}/i', $options['text'])) {
				$options['text'] = $this->defaultOptions['text'];
			}
			
			// if the font file hasn't been chosen, set it
			if(!isset($options['font']) || !preg_match('/.+\.ttf/i', $options['font'])) {
				$options['font'] = $this->defaultOptions['font'];
				$error = true;
			}
			
			// make sure the text formats are set
			if(!isset($options['no_comments_format']) || strlen($options['no_comments_format']) == 0) {
				$options['no_comments_format'] = $this->defaultOptions['no_comments_format'];
				$error = true;
			}
			if(!isset($options['num_comments_format']) || strpos($options['num_comments_format'], '%d') === false) {
				$options['num_comments_format'] = $this->defaultOptions['num_comments_format'];
				$error = true;
			}
			
			//
			if(!isset($options['the_content']) || !is_bool($options['the_content'])) {
				$options['the_content'] = $this->defaultOptions['the_content'];
			}
			if(!isset($options['the_excerpt']) || !is_bool($options['the_excerpt'])) {
				$options['the_excerpt'] = $this->defaultOptions['the_excerpt'];
			}
			
		}
		else {
			$options = $this->defaultOptions;
			$error = true;
		}
		
		if($update_on_error && $error) 
			$this->updateOptions($this->defaultOptions);

		return $options;

	}
		
	// Saves the admin options to the database.
	function updateOptions($options) {
		$options = $this->checkOptions($options, false);
		update_option($this->optionName, $options);
	}
	
	
	// returns an array of the RGB values for the hex color
	function fcn_get_colors($color='text') {
		if(!isset($this->options[$color]))
			return false;
		$color = $this->options[$color];
		$rgb = array();
		$rgb['red'] = hexdec( substr($color, 0, 2) );
		$rgb['green'] = hexdec( substr($color, 2, 2) );
		$rgb['blue'] = hexdec( substr($color, 4, 2) );
		return $rgb;
	}

	// output the image to the browser
	function fcn_draw_image($text) {
	
		// grab our options
		$font = 'fonts/'.$this->options['font'];
		$rgb_back = $this->fcn_get_colors('background');
		$rgb_text = $this->fcn_get_colors('text');
		
		// determine appropriate text placement
		$box = imagettfbbox(10, 0, $font, $text);
		$textWidth = $box[2] - $box[0];
		$textHeight = $box[5] - $box[3];
		$width = $textWidth + 10;
		$height = 30; // might make this an option
		$posLeft = ($width - $textWidth) / 2;
		$posTop = ($height - $textHeight) / 2;
		
		// creates a blank png image with the determined height and width values
		$image = imagecreate($width, $height);
		
		// allocate colors
		$background = imagecolorallocate($image, $rgb_back['red'], $rgb_back['green'], $rgb_back['blue']);
		$forground = imagecolorallocate($image, $rgb_text['red'], $rgb_text['green'], $rgb_text['blue']);
		
		// fill our background
		imagefill($image, 0, 0, $background);
		
		// draw our text on the image
		imagettftext($image, 10, 0, $posLeft, $posTop, $forground, $font, $text);
		
		// output out png image to the browser
		imagepng($image);
		
		// kill the image memory
		imagedestroy($image);
		
}
	
	// upload a .ttf font file
	function uploadFont() {
		if(!isset($_FILES['font'])) {
			return false;
		}
		
		$_FILE = $_FILES['font'];
		
		if($_FILE['error'] > 0) {
    		$this->message = 'FILE ERROR: Return Code: ' . $_FILE['error'];
    		return false;
    	}
    	
		if(file_exists('upload/' . $_FILE['name'])) {
			$this->message = $_FILE['name'] . ' already exists in ' . dirname(__FILE__) . '/upload/';
			return false;
		}
		
		$file = dirname(__FILE__).'/fonts/'.$_FILE['name'];
		
		if(file_exists($file)) {
			$this->message = $_FILE['name'] . ' already exists.';
			return false;
		}
		
		if(!move_uploaded_file($_FILE['tmp_name'], $file)) {
			$this->message = 'Error moving file!';
			return false;
		}
		else {
			$this->message = 'Successfully Uploaded: &quot;' . $_FILE['name'] . '&quot;';
			return true;
		}
	}
	
	// add any/all admin pages to the appropriate page(s)
	function add_admin_pages() {
		add_submenu_page('options-general.php', "Feed Comments Number", "Feed Comments Number", 10, "Feed Comments Number", array(&$this,"output_sub_admin_page_0"));
	}
		
	// Outputs the HTML for the admin sub page.
	function output_sub_admin_page_0() {
			?>
		<style type="text/css">
		.wrap {
			padding: 5px;
		}
		td input {
			text-align: center;
		}
		tr td {
			width: 250px;
		}
		tr td+td {
			width: 200px;
		}
		table {
			padding: 15px;
		}
		.wrap abbr {
			border-bottom: 1px dotted #000;
		}
		.wrap label {
			padding-right: 5px;
		}
		</style>
		<div class="wrap">
			<h2>Add a "Comments Number" image link to your feeds!</h2>
			<p>This puts an image link to the comments section of your post to the bottom of your content for each feed item.<br />Below are two examples, showing what it looks like if there are no comments on that post and if there are 123.</p>
			<p>Color values should be 6 digit hex codes, otherwise it will resort to defaults.<br />I recommend using <a href="http://www.colorpicker.com/" target="_blank">http://www.colorpicker.com/</a> to find various colors you might like to use.<br /><em>(Note: leave the # mark out)</em></p>
			<form name="colors" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
			<table cellspacing="15">
				<tr>
					<td><img src="<?php echo $this->url ?>/image.php?sample=0" title="Shown if there are no comments" alt="" /></td>
					<td><img src="<?php echo $this->url; ?>/image.php?sample=123" title="Example image for 123 Comments" alt="" /></td>
				</tr>
				<tr>
					<td><abbr title="default: FFFFFF (white)">Background Color:</abbr></td>
					<td><input type="text" name="background" id="background" value="<?php echo $this->options['background']; ?>" size="6" maxlength="6" /></td>
				</tr>
				<tr>
					<td><abbr title="default: 0000FF (blue)">Text Color:</abbr></td>
					<td><input type="text" name="text" id="text" value="<?php echo $this->options['text']; ?>" size="6" maxlength="6" /></td>
				</tr>
				<tr>
					<td>Text for Zero Comments:</td>
					<td><input type="text" name="no_comments_format" value="<?php echo $this->options['no_comments_format']; ?>" /></td>
				</tr>
				<tr>
					<td>Format for &gt; Zero Comments:<br /><small>%d converts to the number of comments</small></td>
					<td><input type="text" name="num_comments_format" value="<?php echo $this->options['num_comments_format']; ?>" /></td>
				</tr>
				<tr>
					<td><label>Choose Font: </label></td>
					<td>
						<select name="font" id="font">
		<?php foreach(glob(ABSPATH.'/wp-content/plugins/feed-comments-number/fonts/*.ttf') as $font) : 
					$font = basename($font);
					$selected = ($this->options['font'] == $font) ? 'selected="selected"' : '';
		?>
							<option value="<?php echo basename($font); ?>" <?php echo $selected; ?>><?php echo basename($font); ?></option>
		<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Placement Options:<br /><small><em>Note: unchecking both of these effectively deactivates the plugin.</em></small></td>
					<td>
						<label><abbr title="Places the image link at the end of the full post content in the feed">Feed Content</abbr></label><input type="checkbox" name="the_content" value="Feed Content" <?php if($this->options['the_content'] === true) echo 'checked="checked"'; ?> />
						<br /><br />
						<label><abbr title="Places the link at the end of the post excerpt in the feed">Feed Excerpt</abbr></label><input type="checkbox" name="the_excerpt" value="Feed Excerpt" <?php if($this->options['the_excerpt'] === true) echo 'checked="checked"'; ?> />
					</td>
				</tr>
				<tr>
					<td></td>
					<td><p><input type="submit" name="submit-fcn-options" id="submit-fcn-options" value="Save Changes" /></p></td>
				</tr>
			</table>
			</form>
			<br />
			<?php if($this->message != '') { echo '<p class="error">',$this->message,'</p>'; } ?>
			<form name="font-upload" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data" style="padding:15px;">
				<p><label>Upload a .ttf font: </label><input type="file" name="font" id="font" /></p>
				<input type="submit" name="upload_font" value="Upload Font" />
			</form>
		</div>
			<?php
	} 
		
	// Called by the filter the_content & the_content_rss & the_excerpt_rss
	function the_content_intercept($content) {
		if(is_feed()) {
			$content .= '<br /><a href="'.get_the_guid().'#comments" title="Comments on &quot;'.get_the_title().'&quot;"><img src="'.get_bloginfo('url').'/wp-content/plugins/feed-comments-number/image.php?'.get_the_ID().'" alt="Comments" /></a>';
		}
		return $content;
	}

} // end class
} // end if !class

// instantiate the class
if(class_exists('feed_comments_number')) {
	$feed_comments_number = new feed_comments_number();
}

?>