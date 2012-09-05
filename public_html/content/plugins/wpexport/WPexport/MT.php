<?php
/*MoveableType export Plugin for WordPress*/
function MT_short_desc() {
		echo "Export WordPress data in MoveableType (MTimport) format";
}

function MT_title() {
		echo "MoveableType";
} 

function MT_export_display() {
		echo "<textarea name=\"WPexport\" id=\"WPexport\" rows=20 cols=100 />";	
		echo MT_export(); 
		echo "</textarea>";
}

function MT_export() {
		global $wpdb;
		$output = '';

$query = "SELECT $wpdb->posts.post_date, $wpdb->posts.post_content, $wpdb->posts.post_title, $wpdb->users.user_nickname, $wpdb->posts.post_status, $wpdb->comments.comment_author,  $wpdb->comments.comment_content, $wpdb->comments.comment_author_email,  $wpdb->comments.comment_author_url,  $wpdb->comments.comment_date,  $wpdb->post2cat.category_id, $wpdb->categories.cat_name FROM $wpdb->comments RIGHT  OUTER  JOIN  $wpdb->posts  ON (  $wpdb->comments.comment_post_ID  =  $wpdb->posts.ID  ) LEFT  OUTER  JOIN  $wpdb->users  ON (  $wpdb->posts.post_author  =  $wpdb->users.ID  ) LEFT OUTER  JOIN  $wpdb->post2cat  ON (  $wpdb->posts.ID  =  $wpdb->post2cat.post_id ) LEFT OUTER JOIN $wpdb->categories ON ($wpdb->post2cat.category_id = $wpdb->categories.cat_ID) ";

     $result = $wpdb->query($query);
        if ($result){
        for ($i = 0; $i < $result; $i++) {
	   $row = $wpdb->get_row(null,OBJECT,$i);

	    if ($prev_entry == $row->post_date) {
				$present_cat = $row->cat_name;
				if ($prev_cat == $present_cat) {
					if ($row->comment_content) {
						$output .= "COMMENT:\n";
						$output .=  "AUTHOR: ".stripslashes($row->comment_author)."\n";
						$output .=  "EMAIL: ".stripslashes($row->comment_author_email)."\n";
						$output .=  "URL: ".stripslashes($row->comment_author_url)."\n";
						$output .=  "DATE: ".date("m/d/Y h:m:s A",(strtotime($row->comment_date)))."\n";
						$output .=  stripslashes(str_replace("<br />", "", $row->comment_content))."\n";
						$output .=  "-----\n";
					}
				}
            } else {
		if ($i > 0 ) $output .= "--------\n";
		$output .=  "\n";
		$output .=  "AUTHOR: ".stripslashes($row->user_nickname)."\n";
		$output .=  "TITLE: ".stripslashes($row->post_title)."\n";
		$status = stripslashes($row->post_status);
		if ($status =='publish') {
			$output .=  "STATUS: ".$status."\n";
		} else {
			$output .=  "STATUS: draft\n";
		}

		$date = $row->post_date;
		$output .=  "DATE: ".date("m/d/Y h:m:s A",(strtotime($date)))."\n";
		$output .=  "CATEGORY: ".stripslashes($row->cat_name)."\n";
		$output .=  "-----\n";
		$output .=  "BODY:\n";
		$output .=  stripslashes(str_replace("<br />", "", $row->post_content))."\n";
		$prev_entry = $row->post_date;
		$prev_cat = $row->cat_name;
		if ($row->comment_content != "") {
			$output .=  "-----\n";
			$output .=  "COMMENT:\n";
			$output .=  "AUTHOR: ".stripslashes($row->comment_author)."\n";
			$output .=  "EMAIL: ".stripslashes($row->comment_author_email)."\n";
			$output .=  "URL: ".stripslashes($row->comment_author_url)."\n";
			$output .=  "DATE: ".date("m/d/Y h:m:s A",(strtotime($row->comment_date)))."\n";

			$output .=  stripslashes(str_replace("<br />", "", $row->comment_content))."\n";
			$output .=  "-----\n";
		}
		$output .=  "\n";
		$output .=  "--------\n";
              }
	    } 
	}
return $output;
}
?>
