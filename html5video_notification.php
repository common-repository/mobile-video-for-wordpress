<?php
/*
License: Copyright 2010 Daniel Watrous, All Rights Reserved (see README.txt in the plugin directory)
*/

require_once("html5video_globals.php");

// attempt to load wordpress files for easy access to options and other details
$wp_root = dirname(__FILE__) .'/../../../';
if(file_exists($wp_root . 'wp-load.php')) {
	require_once($wp_root . "wp-load.php");
} else if(file_exists($wp_root . 'wp-config.php')) {
	require_once($wp_root . "wp-config.php");
} else {
	exit;
}

// extract notification details from request
$json_str = file_get_contents('php://input');
$json_arr = json_decode($json_str);
$state = $json_arr->job->state;

// if complete, update post_meta
if($state == "finished")
{
	global $encoding_status_key;
	// identify all values needed
	$encoding_id = $json_arr->job->id;
	$postid = $_GET['postid'];
	$encoding_status = "$encoding_id,2";

	// update post_meta and send admin notice of completion
	update_post_meta($postid, $encoding_status_key, $encoding_status);
	$admin_email = get_option('admin_email');
	$message = "Job finished successfully Post Id: $postid and JobId: $encoding_id ";
	mail($admin_email,'Zencoder API Notification: Job Finished Successfully', $message);
}

?>