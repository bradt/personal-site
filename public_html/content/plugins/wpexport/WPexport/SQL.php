<?php
/*MoveableType export Plugin for WordPress*/
function SQL_short_desc() {
		echo "Export WordPress data in SQL (MySQLdump) format <br> This can take some time, please be patient";
}

function SQL_title() {
		echo "SQL";
} 


function SQL_export_display() {
	echo "<textarea name=\"WPexport\" id=\"WPexport\" rows=20 cols=100 />";
	echo SQL_export();
	echo "</textarea>";
}

function SQL_export() {
        
	global $wpdb;

	$command="/usr/local/apps/mysql/bin/mysqldump -u ".DB_USER." --password=".DB_PASSWORD." -h ".DB_HOST." ".DB_NAME;
	$command .= " $wpdb->categories $wpdb->comments $wpdb->linkcategories $wpdb->links $wpdb->options $wpdb->post2cat $wpdb->postmeta $wpdb->posts $wpdb->users";
	$output = system($command);

	return "$output";
}
?>
