<?php
$version_checks = array(
	"$plugin_slug.php" => array( '@Version:\s+(.*)\n@' => 'header' ),
	'version.php' => array( "@\\\$GLOBALS\\['wpmdb_meta'\\]\\['wp-migrate-db-pro-cli'\\]\\['version'\\] = '(.*?)';@" => 'global variable' )
);
