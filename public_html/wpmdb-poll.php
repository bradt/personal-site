<?php
if ( !isset( $_POST['willing-pay'] ) ) {
	echo "Please choose an option.";
	exit;
}

$mysqli = new mysqli( 'localhost', 'bradtca_wpmdb', 'Bz8A6iqkYKnnJx', 'bradtca_wpmdb' );

$fields = array( 'willing-pay', 'how-much', 'notify-me', 'notify-email', 'comments' );

$stmt = $mysqli->prepare( "INSERT INTO submission VALUES ( null, ?, ?, ?, ?, ?, NOW())" );

if ( !$_POST['willing-pay'] ) {
	$_POST['willing-pay'] = 'No';
}

if ( !$_POST['notify-me'] ) {
	$_POST['notify-me'] = 'No';
}

foreach ( $fields as $field ) {
	if ( !isset( $_POST[$field] ) ) {
		$values[] = '';
	}
	else {
		$values[] = $_POST[$field];
	}
}

$types = 'sssss';
$params = array();
foreach ($values as $k => &$value) {
	$params[$k] = &$value;
}
array_unshift( $params, $types );
call_user_func_array( array( $stmt, 'bind_param' ), $params );

$stmt->execute();

if ( $stmt->error ) {
	echo "Error: ", $stmt->error;
}

$stmt->close();
