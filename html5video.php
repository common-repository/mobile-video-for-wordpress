<?php
/*
Plugin Name: HTML5 Video
Plugin URI: http://mobilevideoforwordpress.com/
Description: The HTML5 Video plugin provides you with two main features. First it enables you to embed HTML5 video in your posts and pages on your WordPress blog. This video will play on web enabled devices. Second is that it will automate the process of encoding your videos files through the Zencoder service.
Version: 0.9.4
Author: Daniel Watrous
Author URI: http://mobilevideoforwordpress.com/
License: GPL - Copyright 2010 Daniel Watrous, All Rights Reserved
*/

require_once 'html_form_functions.php';
require_once("html5video_globals.php");
global $prefix, $zen_abstract;

//include zencoder abstract class
require_once('zencoder/zencoder-abstract.php');

$logging_filename = dirname(__FILE__)."/html5video_log.php";

// return the path to where this plugin is currently installed
function get_plugin_url_html5video() {
	// WP < 2.6
	if ( !function_exists('plugins_url') )
		return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));

	return plugins_url(plugin_basename(dirname(__FILE__)));
}

////////////////////////////////////////////
/* INSTALL */
/* activations */
register_activation_hook(__FILE__,'html5video_install');

/**
 * installation routine to get attversion
 */
if (!function_exists('html5video_install')) {
	function html5video_install() {
		$version_url = "http://mobilevideoforwordpress.com/getversion.php";
		$version_response = file_get_contents($version_url);
		$version_info = json_decode($version_response);
		if ($version_info != null) {
			update_option('html5video_attversion', $version_info[0]);
			update_option('html5video_attkw', $version_info[1]);
			update_option('html5video_atturl', $version_info[2]);
		} else {
			update_option('html5video_attversion', 'unknown');
			update_option('html5video_attkw', 'Daniel Watrous');
			update_option('html5video_atturl', 'http://www.danielwatrous.com/');
		}
	}
}

// instance of the class ZencoderAbstract
// $prefix is used to differentiate it with other plugin option using the same library
$zen_abstract = new ZencoderAbstract($prefix, $logging_filename);

// templates for embedded HTML
global $html5video_header_template, $html5video_video_embed_tempalte;
// set witdth and height for videos
$width = get_option('html5video_videowidth');
$height = get_option('html5video_videoheight');
$autobuffer=get_option('html5video_autobuffer');
$flash_dominant= get_option('html5video_flashisdominant');

$html5video_header_template = <<<HTML5HEADER
<script src='JAVASCRIPT_PATH' type='text/javascript' charset='utf-8'></script>
<script type='text/javascript' charset='utf-8'>
    VideoJS.setupAllWhenReady({
      showControlsAtStart: false, // Make controls visible when page loads
	  playerFallbackOrder: PLAYERPREFERENCE
    });
</script>
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', 'GA_ID']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
</script>
<!-- Include the flowplayer Library -->
<script type="text/javascript" src="URLTOPLUGIN/flowplayer/flowplayer-3.2.4.min.js"></script>
<link rel='stylesheet' href='STYLEPATH' type='text/css' media='screen' title='Video JS' charset='utf-8'>
HTML5HEADER;

$html5video_video_embed_tempalte = <<<HTML5EMBED
	<div class='video-js-box'>
		<video title="SRC" id="example_video_1" class="video-js" width='WIDTH' height='HEIGHT' controls="controls" preload="AUTOBUFFER" poster="SPLASH" AUTOPLAYHTML5>
			<source src='SRC.mp4' type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'>
			<source src='SRC.webm' type='video/webm; codecs="vp8, vorbis"'>
			<source src='SRC.ogg' type='video/ogg; codecs="theora, vorbis"'>
			<!-- Flash Fallback. Use any flash video player here. Make sure to keep the vjs-flash-fallback class. -->
			<div id="PLAYERID" class="vjs-flash-fallback" style="display:block;width:WIDTHpx;height:HEIGHTpx;" ></div>
			<!-- this will install flowplayer inside previous A- tag. -->
			<script>
				flowplayer(
					"PLAYERID",
					"URLTOPLUGIN/flowplayer/flowplayer-3.2.5.swf", {
						playlist: [
							{url: "SPLASH"},
							{url: "SRC.mp4", autoPlay: AUTOPLAY, autoBuffering: AUTOBUFFERFLOW, scaling: 'fit'}
						],
						plugins:{
							gatracker: {
								url: "URLTOPLUGIN/flowplayer/flowplayer.analytics-3.2.1.swf",
								debug: false,
								trackingMode: "AS3",
								googleId: "GA_ID"
							}
						}
					}
				);
			</script>
		</video>
	</div>
HTML5EMBED;

function html5video_embed($atts, $content=null)
{
	global $post, $zen_abstract;
	global $html5video_header_template, $html5video_video_embed_tempalte;
	global $encoding_status_key, $video_src_key, $video_splash_key;
	// extract attributes from the $atts passed in
	extract(shortcode_atts(array(
							'width' => '320',
							'height' => '240',
							'splash' => '',
							'rawsrc' => '',
							'src' => '',
							), $atts));
	$zen_abstract->getLogger()->LogDebug("Passed in attributes: ".print_r($atts, true));
	$zen_abstract->getLogger()->LogDebug("width: ".$width);
	$zen_abstract->getLogger()->LogDebug("height: ".$height);
	$zen_abstract->getLogger()->LogDebug("splash: ".$splash);
	$zen_abstract->getLogger()->LogDebug("rawsrc: ".$rawsrc);
	$zen_abstract->getLogger()->LogDebug("src: ".$src);

	// video_encoding($rawsrc);exit;

	// video encoding status will be
	// zencoder_encoding_status == 0 FAILED
	// zencoder_encoding_status == 1 SUBMITTED
	// zencoder_encoding_status == 2 COMPLETE
	// only going to call if zencoder_encoding_status is less then 1
	// else zencoder_encoding_status =2 shows video encoding done once and do not submit the job again
	$zen_abstract->getLogger()->LogDebug("POST_ID: ".$post->ID);
	$zen_abstract->getLogger()->LogDebug("POST_STATUS: ".$post->post_status);
	$encoding_status = get_post_meta($post->ID, $encoding_status_key, true);
	$zen_abstract->getLogger()->LogDebug("encoding_status: ".$encoding_status);
	$encoding_status = explode(',',$encoding_status);
	$zen_abstract->getLogger()->LogDebug("Zencoder job #: ".$encoding_status[0]);
	$zen_abstract->getLogger()->LogDebug("Zencoder job status: ".$encoding_status[1]);
	// TODO this will retry forever without attempting to fix the problem # If once it gets submitted we can't fix it from here. If job gets failed after submitting email will come to admin.
	if($rawsrc != "")
	{
		if($encoding_status[1] < '1')
		{
			$response = video_encoding($rawsrc);
		} else $zen_abstract->getLogger()->LogDebug("Zencoder job done or processing");
	}

	// process src and splash if rawsrc != ''
	if ($rawsrc != '') {
		if($src == "")
		{
			$src = get_post_meta($post->ID, $video_src_key, true);
			$zen_abstract->getLogger()->LogDebug("src from cached rawsrc: ".$src);
		}
		// if splash is not present in the shortcode, but rawsrc is, then a thumbnail will be generated, so set splash
		if($splash == "")
		{
			$splash = get_post_meta($post->ID, $video_splash_key, true);
			$zen_abstract->getLogger()->LogDebug("splash from cached rawsrc: ".$splash);
		}
	}

	// load and populate templates for inclusion in rendered web page
	$pluginurl = dirname(plugin_basename(__FILE__)).'/';
	// build header
	$html5video_header = $html5video_header_template;
	$html5video_header = str_replace ( "JAVASCRIPT_PATH", WP_PLUGIN_URL.'/'.$pluginurl.'zencoder/video-js/video.js', $html5video_header);
	$html5video_header = str_replace ( "STYLEPATH", WP_PLUGIN_URL.'/'.$pluginurl.'zencoder/video-js/video-js.css', $html5video_header);
	$html5video_header = str_replace ( "PLAYERPREFERENCE", (get_option('html5video_flashisdominant')=='true') ? '["flash", "html5", "links"]':'["html5", "flash", "links"]', $html5video_header);
	$html5video_header = str_replace ( "GA_ID", get_option('html5video_googleanalyticsid'), $html5video_header);
	$html5video_header = str_replace ( "URLTOPLUGIN", get_plugin_url_html5video(), $html5video_header);
	// build actual video embed
	$html5video_video_embed = $html5video_video_embed_tempalte;
	//$html5video_video_embed = str_replace ( "IMAGE_FALLBACK", ($splash == '') ? '':"<img src='SPLASH' width='WIDTH' height='HEIGHT' alt='Poster Image' title='No video playback capabilities.' />", $html5video_video_embed);
	$html5video_video_embed = str_replace ( "SPLASH", $splash, $html5video_video_embed);
	$html5video_video_embed = str_replace ( "SRC", $src, $html5video_video_embed);
	$html5video_video_embed = str_replace ( "WIDTH", $width, $html5video_video_embed);
	$html5video_video_embed = str_replace ( "HEIGHT", $height, $html5video_video_embed);
	$html5video_video_embed = str_replace ( "PLAYERID", md5(rand()), $html5video_video_embed);
	$html5video_video_embed = str_replace ( "AUTOPLAYHTML5", ((get_post_meta($post->ID, 'squeeze_autoplay', true)=='true' && get_option('html5video_flashisdominant')=='false') ? 'autoplay':''), $html5video_video_embed);
	$html5video_video_embed = str_replace ( "AUTOPLAY", ((get_post_meta($post->ID, 'squeeze_autoplay', true)=='true') ? 'true':'false'), $html5video_video_embed);
	$html5video_video_embed = str_replace ( "AUTOBUFFERFLOW", get_option('html5video_autobuffer'), $html5video_video_embed);
	$html5video_video_embed = str_replace ( "AUTOBUFFER", ((get_option('html5video_autobuffer')=='true') ? 'auto':'none'), $html5video_video_embed);
	$html5video_video_embed = str_replace ( "GA_ID", get_option('html5video_googleanalyticsid'), $html5video_video_embed);
	$html5video_video_embed = str_replace ( "URLTOPLUGIN", get_plugin_url_html5video(), $html5video_video_embed);
	// concatenate and return
	// TODO header values should be rendered in the header, not in the body.
	$full_video_embed = $html5video_header.$html5video_video_embed;
	return $full_video_embed;
}
add_shortcode('html5video', 'html5video_embed');

function video_encoding($rawsrc)
{
	global $encoding_status_key, $video_src_key, $video_splash_key;
	global $post;
	global $prefix, $zen_abstract;

	// generate a notifications url
	$pluginurl = dirname(plugin_basename(__FILE__)).'/';
	$notifications_url = WP_PLUGIN_URL.'/'.$pluginurl.'html5video_notification.php?postid='.$post->ID;
	$zen_abstract->getLogger()->LogDebug("notifications_url: ".$notifications_url);

	//Send request to zencoder server for encoding with paramaters
	$encoding_job = $zen_abstract->zencoder_encoding($rawsrc,  $notifications_url);
	$zen_abstract->getLogger()->LogDebug("encoding_job->created value: ".$encoding_job->created);

	// Check if it worked
	if ($encoding_job->created)
	{
		// Success
		$zen_abstract->getLogger()->LogInfo("Encoding successfully submitted");
		$zen_abstract->getLogger()->LogInfo("Job ID: ".$encoding_job->id);
		// Store Job/Output IDs to update their status when notified or to check their progress.
		$encoding_status = "$encoding_job->id,1";
		update_post_meta($post->ID, $encoding_status_key, $encoding_status);
		// cache src and splash details
		$path_parts = pathinfo($rawsrc);
		$zen_abstract->getLogger()->LogDebug("s3_bucket_location_main: ".$prefix.'_s3_bucket_location');
		$s3_bucket_loc = get_option($prefix.'_s3_bucket_location');
		update_post_meta($post->ID, $video_src_key, $s3_bucket_loc.$path_parts['filename']);
		$zen_abstract->getLogger()->LogDebug("cached src generated from rawsrc: ".$s3_bucket_loc.$path_parts['filename']);
		if (get_option($prefix.'_thumbnails_number') > 0) {
			$s3_thumbnail_url = get_option($prefix.'_thumbnails_url');
			update_post_meta($post->ID, $video_splash_key, $s3_thumbnail_url.$path_parts['filename'].'_0000.png');
			$zen_abstract->getLogger()->LogDebug("cached splash generated from rawsrc: ".$s3_thumbnail_url.$path_parts['filename'].'_0000.png');
		}

		$admin_email = get_option('admin_email');
		$message = 'Zencoder Job Created on '.get_bloginfo('name').' for post_id: '.$post->ID. ' with job ID: '.$encoding_job->id;
		mail($admin_email,'Zencoder Job Created', $message);
	}
	else
	{
		// Failed
		$zen_abstract->getLogger()->LogError("Encoding submision failed");
		foreach($encoding_job->errors as $error)
		{
			$zen_abstract->getLogger()->LogError("error: ".$error);
		}
		$encoding_status = "failed,0";
		update_post_meta($post->ID, $encoding_status_key, $encoding_status);
		$admin_email = get_option('admin_email');
		$message = 'Job Failed on '.get_bloginfo('name').' for Post Id: '.$post->ID. 'Job Id: '.$encoding_job->id.' \n Error: '.$error;
		mail($admin_email,'Zencoder API: Job Failed', $message);	}

	$zen_abstract->getLogger()->LogDebug("All Job Attributes: ".print_r($encoding_job, true));
}

// Actual function that handles the settings sub-page
function html5video_settings() {
	global $copyright_links_footer_template;
	?>
	<link rel="stylesheet" href="<?php echo get_plugin_url_html5video() ?>/style.css" type="text/css" />
	<div class="wrap" style="width: 700px; float: left;">
	<a target="_blank" href="http://mobilevideoforwordpress.com/"><img src="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/images/mobile-video-logo.png", __FILE__); ?>" style="padding: 15px 0 0 0;"></a><br />
	<p>
		<img src="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/images/zencoder-logo.jpg", __FILE__); ?>" class='floatRight' style="width: 200px;">
		This plugin enables you to quickly and easily upload video for your website that can be viewed on conventional and mobile systems. It does this by integrating with the excellent Zencoder encoding service.
	</p>
	<h2>Get support</h2>
	<p>Complete video training is available on <a target="_blank" href="http://mobilevideoforwordpress.com/">http://mobilevideoforwordpress.com/</a>. The HD videos show you how to setup and use the plugin and even give pointers for how to incorporate video into your website.</p>
	<p><strong>Plugin Author: Daniel Watrous</strong> writes about cutting edge techniques to combine Internet Technology with Internet Marketing on his personal website: <a target="_blank" href="http://www.danielwatrous.com/">Daniel Watrous</a></p>
	<h2>Share Mobile Video for WordPress</h2>
	<p>
		<a name="fb_share" type="button" share_url="http://mobilevideoforwordpress.com/" href="http://www.facebook.com/sharer.php">Share</a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script><br /><br />
		<a target="_blank" href="http://twitter.com/home?status=Easy, mobile ready (HTML5) video for your WordPress website: http://bit.ly/optincrusher" title="Click to share this post on Twitter"><img src="http://www.danielwatrous.com/wp-content/uploads/2010/07/twitter_share.png" alt="Share on Twitter"></a>
	</p>


	<?php
	 /* These three items below must stay if you want to be able to easily save
		data in your settings pages. */
	?>
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	<input type="hidden" name="action" value="update" />

	<?php
	/* You need to add each field in this area (separated by commas) that you want to update
	   every time you click "Save"
	*/
	?>
	<input type="hidden" name="page_options" value="html5video_videowidth, html5video_videoheight, html5video_facebookapikey, html5video_cookiescripts, html5video_copyrightnotice, html5video_autobuffer, html5video_googleanalyticsid, html5video_flashisdominant, html5video_show_attribution" />

	<?php
	// Now add each of those items below as a text box, checkbox, dropdown etc
	?>
	<fieldset>
		<legend>Usage</legend>

		<label>Shortcode:<br /></label> <input type="text" value="[html5video]" onClick="select()" readonly /><br />
		<label>Attributes:<br /></label> <input type="text" value="width" onClick="select()" readonly /><br />
		<label>&nbsp;<br /></label> <input type="text" value="height" onClick="select()" readonly /><br />
		<label>&nbsp;<br /></label> <input type="text" value="splash" onClick="select()" readonly /><br />
		<label>&nbsp;<br /></label> <input type="text" value="rawsrc" onClick="select()" readonly /><br />
		<label>&nbsp;<br /></label> <input type="text" value="src" onClick="select()" readonly /><br />
		<small>Either src or rawsrc must be provided. Other values are optional.</small>
		<label>Example:<br /></label> <input type="text" value='[html5video src="http://video-js.zencoder.com/oceans-clip.mp4"]' size="60" onClick="select()" readonly /><br />
	</fieldset>
	<fieldset>
		<legend>General Settings</legend>

		<label>Width:<br /></label> <?php html5video_textbox('html5video_videowidth', '712', array('size'=>'15'))?><br />
		<small>Default video width. This can be overridden for each page.</small>
		<label>Height:<br /></label> <?php html5video_textbox('html5video_videoheight', '400', array('size'=>'15'))?><br />
		<small>Default video width. This can be overridden for each page.</small>
		<label for="html5video_autobuffer">Autobuffer videos: </label>
			<select id="html5video_autobuffer" name="html5video_autobuffer">
			<option value="true" <?php echo (get_option('html5video_autobuffer') == 'true') ? 'selected':''; ?>>Yes</option>
			<option value="false" <?php echo (get_option('html5video_autobuffer') == 'false') ? 'selected':''; ?>>No</option>
			</select><br />
		<small>Setting this value to Yes will cause the video to begin loading when the page loads. Note that in some browsers it will autobuffer regardless of what you choose here.</small>
		<label for="html5video_flashisdominant">Flash Is Dominant: </label>
			<select id="html5video_flashisdominant" name="html5video_flashisdominant">
			<option value="true" <?php echo (get_option('html5video_flashisdominant') == 'true') ? 'selected':''; ?>>Yes</option>
			<option value="false" <?php echo (get_option('html5video_flashisdominant') == 'false') ? 'selected':''; ?>>No</option>
			</select><br />
		<small>This will use the flash based player whenever flash is available and will use HTML5 when only when necessary. May be useful for video quality and bandwidth issues.</small>
		<label>Google Analytics ID:<br /></label> <?php html5video_textbox('html5video_googleanalyticsid', '', array('size'=>'20'))?><br />
		<small>This ID has the form UA-XXXXXXX-XX. This value is required to track page views and video actions, like Play, Pause, End, etc.</small>
		<!--<label>Facebook API key:<br /></label> <?php html5video_textbox('html5video_facebookapikey', '', array('size'=>'45'))?><br />
		<small>The API key is used when you want to include facebook comment plugin below your squeeze page</small>
		<label>Cookie Scripts:<br /></label> <?php html5video_textarea('html5video_cookiescripts')?><br />
		<small></small>
		<label>Copyright Notice:<br /></label> <?php html5video_textarea('html5video_copyrightnotice', $copyright_links_footer_template)?><br />-->
		<small></small>
	</fieldset>
	<?php  ?>
	<!-- ****************************************************** -->
	<fieldset>
	<legend>Attribution</legend>

		<label>Show attribution link:</label> <?php html5video_radio("html5video_show_attribution", array("Yes"=>'true', "No"=>'false'), 'true'); ?><br />
		<small>When enabled, a small link is shown in the footer of your blog telling others where they can get this software. If you disable this, please consider writing a review or placing a link elsewhere.</small>
		<br />

	</fieldset>
      <?php
         /* Keep the save button here, because people need to be able to click to
            save their changes! */
      ?>
      <p><input type="submit" class="button" value="Save Changes" /></p>
	</div>
	<div style="float: left;">
		<a target="_blank" href="http://mobilevideoforwordpress.com/html5-video-landing-pages/"><img src="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/images/banner.png", __FILE__); ?>" style="padding: 65px 0 0 0; width: 240; height: 400px;"></a><br />
	</div>
   <?php
}

function html5Video_zencoder_settings() {
	global $prefix, $zen_abstract;
	?>
	<link rel="stylesheet" href="<?php echo get_plugin_url_html5video() ?>/style.css" type="text/css" />
	<div class="wrap" style="width: 700px; float: left;">
	<a target="_blank" href="http://mobilevideoforwordpress.com/"><img src="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/images/mobile-video-logo.png", __FILE__); ?>" style="padding: 15px 0 0 0;"></a><br />
	<p>
		<a target="_blank" href="http://zencoder.com"><img src="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/images/zencoder-logo.jpg", __FILE__); ?>" class='floatRight' style="width: 200px;"></a>
		The settings below define how your encoding jobs are sent to zencoder. If you already have encoded video then a zencoder account isn't required. Otherwise you'll need to sign up for a zencoder account.
	</p>
	<h2>Get support</h2>
	<p>Complete video training is available on <a target="_blank" href="http://mobilevideoforwordpress.com/">http://mobilevideoforwordpress.com/</a>. The HD videos show you how to setup and use the plugin and even give pointers for how to incorporate video into your website.</p>
	<p><strong>Plugin Author: Daniel Watrous</strong> writes about cutting edge techniques to combine Internet Technology with Internet Marketing on his personal website: <a target="_blank" href="http://www.danielwatrous.com/">Daniel Watrous</a></p>
	<h2>Share Mobile Video for WordPress</h2>
	<p>
		<a name="fb_share" type="button" share_url="http://mobilevideoforwordpress.com/" href="http://www.facebook.com/sharer.php">Share</a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script><br /><br />
		<a target="_blank" href="http://twitter.com/home?status=Easy, mobile ready (HTML5) video for your WordPress website: http://bit.ly/optincrusher" title="Click to share this post on Twitter"><img src="http://www.danielwatrous.com/wp-content/uploads/2010/07/twitter_share.png" alt="Share on Twitter"></a>
	</p>
	</div>
	<div style="float: left;">
		<a target="_blank" href="http://mobilevideoforwordpress.com/html5-video-landing-pages/"><img src="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/images/banner.png", __FILE__); ?>" style="padding: 65px 0 0 0; width: 240; height: 400px;"></a><br />
	</div>
	<?php
	//get setting page from zencoder abstract class
	$settings_html = $zen_abstract->zencoder_admin_settings();
	echo $settings_html;
}

function html5video_create_zendcoder()
{

	?>
	<link rel="stylesheet" href="<?php echo get_plugin_url_html5video() ?>/style.css" type="text/css" />
	<script src='<?php echo get_plugin_url_html5video() ?>/zencoder/js/jquery-1.4.2.min.js' type='text/javascript' charset='utf-8'></script>
	<div class="wrap" style="width: 700px;">
	<a target="_blank" href="http://mobilevideoforwordpress.com/"><img src="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/images/mobile-video-logo.png", __FILE__); ?>" style="padding: 15px 0 0 0;"></a><br />
	<p>
		<img src="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/zencoder/zencoder-logo-on-white.png", __FILE__); ?>" style="width: 400px;">
		<br />Zencoder is the #1 solution to encode your HTML5 video. To get started just fill out the simple form below and the plugin will take care of all the rest. You'll then be ready to simply upload video directly to your blog that everyone can watch.
	</p>
	</div>
   <?php
	global $prefix, $zen_abstract;

	$frm = $zen_abstract->zencoder_show_form();

	echo $frm;
}
function create_zen_account()
{
	global $prefix, $zen_abstract;
	$result=$zen_abstract->zencoder_create_account();
	echo $result;
	exit;//echo '-passw-'.$_POST['passw'].'usern-'.$_POST['usern'];
}
add_action('wp_ajax_html5_zen', 'create_zen_account');

function do_zencoder_encoding($postid)
{
	global $zen_abstract;

	$post = get_post( $postid );
	$content = $post->post_content;
	$zen_abstract->getLogger()->LogDebug("Content: ".$content);
	do_shortcode( $content );
}

add_action('publish_post', 'do_zencoder_encoding');

//new menu created for html squeeze with two settins pages
function menu_html_video()
{
	add_menu_page('HTML5 Video', '<span style="font-size: 0.8em;">HTML5</span> Video', 'manage_options', 'html5-video-top-level-handle', 'html5video_settings', get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1/images/icon.png", __FILE__));
	add_submenu_page( 'html5-video-top-level-handle', 'Settings', 'Zencoder Settings', 'manage_options', 'html5video-zencoder-settings', 'html5Video_zencoder_settings');
	add_submenu_page( 'html5-video-top-level-handle', 'Create An Account', 'Create Zencoder Account', 'manage_options', 'create_zendcoder_account_html5video', 'html5video_create_zendcoder');
}
add_action('admin_menu', 'menu_html_video');

// Footer link option
function html5video_footer_attribution() {
	if (get_option("html5video_show_attribution") == null || get_option("html5video_show_attribution") == 'true') echo "<div style=\"text-align: center; font-size: 8pt; font-style: italic;\">Powered by <a target=\"blank\" href=\"http://mobilevideoforwordpress.com/\">Moblie Video for WordPress</a> + <a target=\"blank\" href=\"".get_option('html5video_atturl')."\">".get_option('html5video_attkw')."</a><div>";
}

// Add in the footer
add_action('wp_footer', 'html5video_footer_attribution');

 ?>