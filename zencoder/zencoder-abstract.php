<?php
// abstract library for zencoder to work with the wordpress plugins
require_once("zencoder-php/Zencoder.php");
require_once('html5_logger.php');

if(!class_exists('ZencoderAbstract')) {
	class ZencoderAbstract{
		//prefix needs to be set from the call to differentiate the plugin name
		var $prefix;
		private $log;
		private $logging_level;
		private $logging_filename;
			
		function ZencoderAbstract($prefix, $logging_filename)
		{
			$this->prefix = $prefix;
			$this->logging_filename = $logging_filename;
			$this->logging_level = html5_logger::DEBUG;
			$this->createLogger();
		}
		
		//common setting page for both the plugin with prefix to know the setting difference
		function zencoder_admin_settings()

		{
			$settings_string = "";
			$api_key = get_option("zencoder_api_key");
			if($api_key == "")
			{
				$settings_string .= "<div class='error'>Please set validate API key.</div>";			
			}
			$pluginurl = dirname(plugin_basename(__FILE__)).'/';
			$jquery =  WP_PLUGIN_URL.'/'.$pluginurl.'js/jquery-1.4.2.min.js';
			$jqueryUi = WP_PLUGIN_URL.'/'.$pluginurl.'js/jquery-ui-1.8.4.custom.min.js';
			$cssUi = WP_PLUGIN_URL.'/'.$pluginurl.'css/jquery-ui-1.8.4.custom.css';
			$settings_string .=  "<script src='".$jquery."' type='text/javascript' charset='utf-8'></script>
				<script src='".$jqueryUi."' type='text/javascript' charset='utf-8'></script>
				<link type='text/css' href=".$cssUi." rel='stylesheet' />
				<style>
					.small { color: #888; padding-top: 3px; font-size:10px;  }
				</style>
				<script type='text/javascript'>
					$(function() {
						$('#accordion').accordion({
						active: false, 
						event: 'click', 				
						collapsible: true,
						autoHeight: false,
						});
					});
					</script>
					
				<div class='wrap'>
					
					<div style='width:500px;'>
					<form method='post' action='options.php'>";
					$settings_string .= wp_nonce_field('update-options');
					$settings_string .=  '<input type="hidden" name="page_options" value="'.$this->prefix.'_test_mode,zencoder_api_key, '.$this->prefix.'_s3_bucket_location,'.$this->prefix.'_output_base_url, '.$this->prefix.'_output_filename,'.$this->prefix.'_output_label_ogg, 
					'.$this->prefix.'_output_label_mp4, '.$this->prefix.'_output_label_webm,  '.$this->prefix.'_notifications_email_address, 
					'.$this->prefix.'_video_codec, '.$this->prefix.'_video_quality, '.$this->prefix.'_video_speed, '.$this->prefix.'_video_width, 
					'.$this->prefix.'_video_height, '.$this->prefix.'_video_upscale, '.$this->prefix.'_thumbnails_number, '.$this->prefix.'_thumbnails_interval, '.$this->prefix.'_thumbnails_size, '.$this->prefix.'_thumbnails_url, '.$this->prefix.'_thumbnails_filename_prefix, 
					'.$this->prefix.'_advanced_video_maximum_frame_rate, '.$this->prefix.'_advanced_video_frame_rate, 
					'.$this->prefix.'_advanced_video_keyframe_interval, '.$this->prefix.'_advanced_video_bitrate, '.$this->prefix.'_advanced_video_bitrate_cap, '.$this->prefix.'_advanced_video_buffer_size, '.$this->prefix.'_advanced_video_skip_video, '.$this->prefix.'_advanced_audio_bitrate, '.$this->prefix.'_advanced_audio_sample_rate, '.$this->prefix.'_advanced_audio_skip_video, '.$this->prefix.'_video_autolevels, 
					'.$this->prefix.'_video_deblock, '.$this->prefix.'_start_clip,'.$this->prefix.'_clip_length,'.$this->prefix.'_public, '.$this->prefix.'_video_denoise, 
					'.$this->prefix.'_advanced_audio_channels, '.$this->prefix.'_advanced_video_deinterlace, '.$this->prefix.'_thumbnails_public_readable, 
					'.$this->prefix.'_audio_quality, '.$this->prefix.'_audio_codec, '.$this->prefix.'_aspect_mode">
						<input type="hidden" name="action" value="update" />					
						<br>';
					$settings_string .=  "<div id='accordion'>
							<h3><a href='#'>API Key & Test Mode</a></h3>
								<div>
									<label>API Key: </label><input type='text' name='zencoder_api_key' value='".get_option('zencoder_api_key')."' size='60' ><br>
										<br><br>						
									
									<label>Test Mode</label><br>
									<input type='text' size='60' name='".$this->prefix."_test_mode' value='".get_option($this->prefix."_test_mode")."'><br>
										<label class='small'>Enter 1 to enable test mode. Encoded videos will be limited to 5 seconds.</label><br>								
							</div>
							<h3><a href='#'>Output Settings</a></h3>
								<div>
									<label>S3 bucket location: </label><input type='text' name='".$this->prefix."_s3_bucket_location' value='".get_option($this->prefix."_s3_bucket_location")."' size='60' ><br>
										<label class='small'>A S3, FTP, or SFTP directory URL where we'll put the transcoded file.</label><br><br>
										
									<!--<label>Output Filename</label><br>
									<input type='text' size='60' name='".$this->prefix."_output_filename' value='".get_option($this->prefix."_output_filename")."'><br>
										<label class='small'>The name of the output file. The extension is important, and must match the output format/codecs.</label><br>
										
									<label>Output Label ogg</label><br>
									<input type='text' size='60' name='".$this->prefix."_output_label_ogg' value='".get_option($this->prefix."_output_label_ogg")."'><br>
										<label class='small'>A label for ogg output.</label><br>
									
									<label>Output Label mp4</label><br>
									<input type='text' size='60' name='".$this->prefix."_output_label_mp4' value='".get_option($this->prefix."_output_label_mp4")."'><br>
										<label class='small'>A label for mp4 output.</label><br>

									<label>Output Label webm</label><br>
									<input type='text' size='60' name='".$this->prefix."_output_label_webm' value='".get_option($this->prefix."_output_label_webm")."'><br>
										<label class='small'>A label for webm output.</label><br>	-->
										
											
								</div>

							<h3><a href='#'>Video Settings</a></h3>			
								<div>
									<!--<label>video codec</label><br>
									<input type='text' size='60' name='".$this->prefix."_video_codec' value='".get_option($this->prefix."_video_codec")."'><br>
										<label class='small'>the video codec to be used.</label><br>-->
										
									<label>Video Quality</label><br>
									<select id='video_quality' name='".$this->prefix."_video_quality'><option value=''></option> 
											<option value='1' "; 
											if(get_option($this->prefix."_video_quality") == '1') $settings_string .=  'selected';
											$settings_string .= ">1 - Poor quality (smaller file)</option> 
											<option value='2'";
											if(get_option($this->prefix."_video_quality") == '2') $settings_string .=  'selected';
											$settings_string .= ">2</option> 
											<option value='3'";
											if(get_option($this->prefix."_video_quality") == '3') $settings_string .=  'selected';
											$settings_string .= ">3 (default)</option> 
											<option value='4'";
											if(get_option($this->prefix."_video_quality") == '4') $settings_string .=  'selected';
											$settings_string .= ">4</option> 
											<option value='5'";
											if(get_option($this->prefix."_video_quality") == '5') $settings_string .=  'selected';
											$settings_string .= ">5 - High quality (larger file)</option>
									</select> <br>
										<label class='small'>a target video quality. affects bitrate and file size</label><br>
									
									<label>Speed</label><br>
									
										<select id='video_speed' name='".$this->prefix."_video_speed'>
										<option value=''></option> 
										<option value='1'";
										if(get_option($this->prefix."_video_speed") == '1') $settings_string .=  'selected';
										$settings_string .= ">1 - Slow (better compression)</option> 
										<option value='2'";
										if(get_option($this->prefix."_video_speed") == '2') $settings_string .=  'selected';
										$settings_string .= ">2</option> 
										<option value='3'";
										if(get_option($this->prefix."_video_speed") == '3') $settings_string .=  'selected';
										$settings_string .= ">3 (default)</option> 
										<option value='4'";
										if(get_option($this->prefix."_video_speed") == '4') $settings_string .=  'selected';
										$settings_string .= ">4</option> 
										<option value='5'";
										if(get_option($this->prefix."_video_speed") == '5') $settings_string .=  'selected';
										$settings_string .= ">5 - Fast (worse compression)</option></select> <br>
										<label class='small'>Speed of encoding. Affects compression</label><br>
										
									<label>Width</label><br>
									<input type='text' size='60' name='".$this->prefix."_video_width' value='".get_option($this->prefix."_video_width")."'><br>
										<label class='small'>The maximum width of the output video (in pixels).</label><br>	
									
									<label>Height</label><br>
									<input type='text' size='60' name='".$this->prefix."_video_height' value='".get_option($this->prefix."_video_height")."'><br>
										<label class='small'>The maximum height of the output video (in pixels).</label><br>
									
									<label>Aspect Mode</label><br>
									<select id='aspect_mode' name='".$this->prefix."_aspect_mode'>
										<option value=''></option> 
										<option value='preserve' "; 
											if(get_option($this->prefix."_aspect_mode") == 'preserve') $settings_string .=  'selected';
										$settings_string .= ">Preserve aspect ratio (default)</option> 
										<option value='crop'";
											if(get_option($this->prefix."_aspect_mode") == 'crop') $settings_string .=  'selected';
										$settings_string .=  ">Crop to fit output aspect ratio</option> 
										<option value='pad' "; 
											if(get_option($this->prefix."_aspect_mode") == 'pad') $settings_string .=  'selected';
										$settings_string .= ">Pad (letterbox) to fit output aspect ratio</option> 
										<option value='stretch'";
										if(get_option($this->prefix."_aspect_mode") == 'pad') $settings_string .=  'selected';
										$settings_string .= ">Stretch (distort) to output aspect ratio</option>
									</select><br>
									<label class='small'>What to do when aspect ratio of input file does not match the target width/height aspect ratio.</label><br>

									<label>Upscale?</label><br>
									<input type='checkbox' size='60' name='".$this->prefix."_video_upscale' value='1' ";
									if(get_option($this->prefix."_video_upscale") == '1') $settings_string .=  'selected';
									$settings_string .= "><br>
										<label class='small'>If the input file is smaller than the target output, should the file be upscaled to the target size?</label>	
										
								</div>	

							<h3><a href='#'>Audio Settings</a></h3>			
								<div>
									<label>Audio Codec</label><br>
									<select id='audio_codec' name='".$this->prefix."_audio_codec'>
										<option value=''></option> 
										<option value='aac'";
											if(get_option($this->prefix."_audio_codec") == 'aac') $settings_string .=  'selected';
										$settings_string .= ">AAC (default for most cases)</option> 
										<option value='mp3'";
										if(get_option($this->prefix."_audio_codec") == 'mp3') $settings_string .=  'selected';
										$settings_string .= ">MP3</option> 
										<option value='vorbis'";
										if(get_option($this->prefix."_audio_codec") == 'vorbis') $settings_string .=  'selected';
										$settings_string .= ">Vorbis (default for VP8 and Theora)</option>
									</select><br>
										<label class='small'>The audio codec to be used.</label><br>
										
									<label>Quality</label><br>
									<select name='".$this->prefix."_audio_quality'>
										<option value=''></option> 
										<option value='1'";
										if(get_option($this->prefix."_audio_quality") == '1') $settings_string .=  'selected';
										$settings_string .= ">1 - Poor quality (smaller file)</option> 
										<option value='2'";
										if(get_option($this->prefix."_audio_quality") == '2') $settings_string .=  'selected';
										$settings_string .= ">2</option> 
										<option value='3'";
										if(get_option($this->prefix."_audio_quality") == '3') $settings_string .=  'selected';
										$settings_string .= ">3 (default)</option> 
										<option value='4'";
										if(get_option($this->prefix."_audio_quality") == '4') $settings_string .=  'selected';
										$settings_string .= ">4</option> 
										<option value='5'";
										if(get_option($this->prefix."_audio_quality") == '5') $settings_string .=  'selected';
										$settings_string .= ">5 - High quality (larger file)</option>
									</select><br>
										<label class='small'>A target audio quality. Affects bitrate and file size.</label><br>		
								</div>

							<h3><a href='#'>Thumbnails</a></h3>			
								<div>
									<label>Number</label><br>
									<input type='text' size='60' name='".$this->prefix."_thumbnails_number' value='".get_option($this->prefix."_thumbnails_number")."'><br>
										<label class='small'>A number of evenly-spaced thumbnails to create.</label><br>
										
									<!--<label>Interval</label><br>
									<input type='text' size='60' name='".$this->prefix."_thumbnails_interval' value='".get_option($this->prefix."_thumbnails_interval")."'><br>
										<label class='small'>Create thumbnails at a regular interval, in seconds.</label><br>

									<label>Size</label><br>
									<input type='text' size='60' name='".$this->prefix."_thumbnails_size' value='".get_option($this->prefix."_thumbnails_size")."'><br>
										<label class='small'>The size of the thumbnails as WIDTH x HEIGHT.</label><br>	-->
									
									<label>Thumbnails URL</label><br>
									<input type='text' size='60' name='".$this->prefix."_thumbnails_url' value='".get_option($this->prefix."_thumbnails_url")."'><br>
										<label class='small'>A S3, FTP, or SFTP URL where we'll put the thumbnails. If this is left empty, the standard output location will be used.</label><br>	
										
									<!--<label>Filename Prefix</label><br>
									<input type='text' size='60' name='".$this->prefix."_thumbnails_filename_prefix' value='".get_option($this->prefix."_thumbnails_filename_prefix")."'><br>
										<label class='small'>A custom filename prefix.</label><br>
									
									<label>Publicly Readable? (if using S3)</label><br>
									 <select id='thumbnails_public_readable' name='".$this->prefix."_thumbnails_public_readable'>
										<option value=''>No</option> 
										<option value='1'";
										if(get_option($this->prefix."_thumbnails_public_readable") == '1') $settings_string .=  'selected';
										$settings_string .= ">Yes</option></select><br>
										<label class='small'>Give S3 READ permission to all users.</label><br>		-->
								</div>
								
							<h3><a href='#'>Advanced Video Settings</a></h3>			
								<div>
									<label>Deinterlace</label><br>
									 <select id='advanced_video_deinterlace' name='".$this->prefix."_advanced_video_deinterlace'>
										<option value=''></option> 
										<option value='detect'";
										if(get_option($this->prefix."_advanced_video_deinterlace") == 'detect') $settings_string .=  'selected';
										$settings_string .=  ">Detect (default)</option> 
										<option value='on'";
										if(get_option($this->prefix."_advanced_video_deinterlace") == 'on') $settings_string .=  'selected';
										$settings_string .= ">On (reduces quality of non-interlaced content)</option> 
										<option value='off'";
										if(get_option($this->prefix."_advanced_video_deinterlace") == 'off') $settings_string .=  'selected';
										$settings_string .= ">Off</option></select> <br>
										<label class='small'>Note that detect mode will auto-detect and deinterlace interlaced content.</label><br>
										
									<label>Maximum Frame Rate</label><br>
									<input type='text' size='60' name='".$this->prefix."_advanced_video_maximum_frame_rate' value='".get_option($this->prefix."_advanced_video_maximum_frame_rate")."'><br>
										<label class='small'>A maximum frame rate cap (in frames per second).</label><br>

									<label>Frame Rate</label><br>
									<input type='text' size='60' name='".$this->prefix."_advanced_video_frame_rate' value='".get_option($this->prefix."_advanced_video_frame_rate")."'><br>
										<label class='small'>Force a specific output frame rate (in frames per second). For best quality, do not use this setting.</label><br>	
									
									<label>Keyframe Interval</label><br>
									<input type='text' size='60' name='".$this->prefix."_advanced_video_keyframe_interval' value='".get_option($this->prefix."_advanced_video_keyframe_interval")."'><br>
										<label class='small'>Creates a keyframe every n frames.</label><br>	
										
									<label>Video Bitrate</label><br>
									<input type='text' size='60' name='".$this->prefix."_advanced_video_bitrate' value='".get_option($this->prefix."_advanced_video_bitrate")."'><br>
										<label class='small'>A target bitrate in kbps. Not necessary if you select a Video Quality setting, unless you want to target a specific bitrate.</label><br>
									
									<label>Bitrate Cap</label><br>
									<input type='text' size='60' name='".$this->prefix."_advanced_video_bitrate_cap' value='".get_option($this->prefix."_advanced_video_bitrate_cap")."'><br>
										<label class='small'>A bitrate cap in kbps, used for streaming servers.</label><br>
										
									<label>Buffer Size</label><br>
									<input type='text' size='60' name='".$this->prefix."_advanced_video_buffer_size' value='".get_option($this->prefix."_advanced_video_buffer_size")."'><br>
										<label class='small'>The buffer size for the bitrate cap in kbps.</label><br>
										
									<label>Skip Video</label><br>
									 <input id='advanced_video_skip_video' name='".$this->prefix."_advanced_video_skip_video' ";
										if(get_option($this->prefix."_advanced_video_skip_video") == '1') $settings_string .=  "checked";
										$settings_string .= " type='checkbox' value='1'/> <br>
										<label class='small'>Return an audio-only file.</label><br>		
								</div>
							<h3><a href='#'>Advanced Audio Settings</a></h3>			
								<div>
									<label>Audio Channels</label><br>
									 <select id='advanced_audio_channels' name='".$this->prefix."_advanced_audio_channels'>
										<option value=''></option> 
										<option value='1'";
										if(get_option($this->prefix."_advanced_audio_channels") == '1') $settings_string .=  'selected';
										$settings_string .= ">1 - Mono</option> 
										<option value='2'";
										if(get_option($this->prefix."_advanced_audio_channels") == '2') $settings_string .=  'selected';
										$settings_string .= ">2 - Stereo (default)</option></select> <br>
										<label class='small'>The number of audio channels.</label><br>
										
									<label>Audio Bitrate</label><br>
									<input type='text' size='60' name='".$this->prefix."_advanced_audio_bitrate' value='".get_option($this->prefix."_advanced_audio_bitrate")."'><br>
										<label class='small'>Total audio bitrate in kbps.</label><br>

									<label>Audio Sample Rate</label><br>
									<input type='text' size='60' name='".$this->prefix."_advanced_audio_sample_rate' value='".get_option($this->prefix."_advanced_audio_sample_rate")."'><br>
										<label class='small'>The sample rate of the audio in hertz. Manually setting this may cause problems, depending on the selected bitrate and number of channels.</label><br>	
									
									<label>Skip Audio</label><br>
									<input id='advanced_audio_skip_video' name='".$this->prefix."_advanced_audio_skip_video' type='checkbox' ";
									if(get_option($this->prefix."_advanced_audio_skip_video") == '1') $settings_string .=  "checked";
									$settings_string .=  " value='1'/> <br>
										<label class='small'>Return a video-only file.</label><br>	
								</div>			
							<h3><a href='#'>Video Optimization</a></h3>			
								<div>
									<label>Autolevels (beta)</label><br>
									  <input id='video_autolevels' name='".$this->prefix."_video_autolevels' type='checkbox'";
										if(get_option($this->prefix."_video_autolevels") == '1') $settings_string .=  "checked";
									  $settings_string .= " value='1' />  <br>
										<label class='small'>Automatic brightness / contrast correction.</label><br>
										
									<label>Deblock (beta)</label><br>
									 <input id='video_deblock' name='".$this->prefix."_video_deblock' type='checkbox' value='1' ";
									 if(get_option($this->prefix."_video_deblock") == '1') $settings_string .=  "checked";
										$settings_string .=  " />  <br>
										<label class='small'>Apply deblocking filter. Useful for highly compressed or blocky input videos.</label><br>

									<label>Denoise (beta)</label><br>
									<select id='video_denoise' name='".$this->prefix."_video_denoise'><option value=''>None</option> 
										<option value='weak'";
											if(get_option($this->prefix."_video_denoise") == 'weak') $settings_string .=  'selected';
										$settings_string .= ">Weak - usually OK for general use</option> 
										<option value='medium'";
										if(get_option($this->prefix."_video_denoise") == 'medium') $settings_string .=  'selected';
										$settings_string .= ">Medium</option> 
										<option value='strong'";
										if(get_option($this->prefix."_video_denoise") == 'strong') $settings_string .=  'selected';
										$settings_string .= ">Strong - beware</option> 
										<option value='strongest'";
										if(get_option($this->prefix."_video_denoise") == 'strongest') $settings_string .=  'selected';
										$settings_string .= ">Strongest - beware, except for Anime</option></select> <br>
										<label class='small'>Apply denoise filter. Generally results in slightly better compression, and slightly slower encoding. Beware of any value higher than 'Weak' (unless you're encoding animation).</label><br>	
									
								</div>	
								
							<h3><a href='#'>S3 Output Permissions</a></h3>			
								<div>
									<label>Publicly Readable</label><br>
									 <select id='public' name='".$this->prefix."_public'>
										<option value=''>No</option> 
										<option value='1' ";
										if(get_option($this->prefix."_public") == '1') $settings_string .=  'selected';
										$settings_string .= ">Yes</option></select> <br>
										<label class='small'>Give the READ permission to all users.</label><br>	
									
								</div>
							<h3><a href='#'>Create Clip</a></h3>			
								<div>
									<label>Start Clip</label><br>
									  <input id='start_clip' name='".$this->prefix."_start_clip' type='text' value='".get_option($this->prefix."_start_clip")."' />  <br>
										<label class='small'>The starting point of a subclip (in timecode or number of seconds).</label><br>
										
									<label>Clip Length</label><br>
									  <input id='clip_length' name='".$this->prefix."_clip_length' type='text' value='".get_option($this->prefix."_clip_length")."' />  <br>
										<label class='small'>The length of the subclip (in timecode or number of seconds).</label><br>	
									
								</div>	
							</div>	
					<br />
					<input type='submit' name='btnSubmit' class='button' value='Update Zencoder Settings'  ></div>
					</form>
					</div>";
					
					return $settings_string;
		}

		//function will send request to zencoder server and create a encoding job 
		function zencoder_encoding($rawsrc, $notifications_url)
		{
			
			// get ogg label
			$label_ogg = ($output_label_ogg = get_option($this->prefix.'_output_label_ogg') && get_option($this->prefix.'_output_label_ogg') != '') ? $output_label_ogg:'ogg';
			$this->log->LogDebug("label_ogg: ".$label_ogg);
			// get mp4 label
			$label_mp4 = ($output_label_mp4 = get_option($this->prefix.'_output_label_mp4') && $output_label_mp4 != "" ) ? $output_label_mp4 : 'mp4';
			$this->log->LogDebug("label_mp4: ".$label_mp4);
			// get webm label
			$label_webm = ($output_label_webm = get_option($this->prefix.'_output_label_webm') && $output_label_webm != "" ) ?  $output_label_webm: 'webm';
			$this->log->LogDebug("label_webm: ".$label_webm);	

			// fetch api_key from settings
			$api_key = get_option('zencoder_api_key');		
			$this->log->LogInfo("api_key: ".$api_key);
			// get test_mode parameter
			$test_mode = get_option($this->prefix.'_test_mode');
			$this->log->LogDebug("test: ".$test_mode);	
			// setting s3 buket location here for now
			$s3_bucket_loc = $this->getS3VideoBucketURL();
			// files publicly readable on S3
			$public = get_option($this->prefix.'_public');
			// get output filename
			$outpath = pathinfo($rawsrc);
			$outfilename = $outpath['filename'];
			$this->log->LogDebug("outfilename: ".$outfilename);
			$outputurl = $s3_bucket_loc.$outfilename;

			// determine whether to include thumbnail
			if (($thumbnails_number = get_option($this->prefix."_thumbnails_number")) > 0) {
				$thumbnail = array();
				$thumbnail["number"] = $thumbnails_number;
				$thumbnail["base_url"] = $this->getS3ThumbnailBucketURL ();
				$thumbnail["prefix"] = ($thumbnails_filename_prefix_set = get_option($this->prefix.'_thumbnails_filename_prefix') != '') ? $thumbnails_filename_prefix_set:$outfilename;
				$thumbnail["public"] = 1;
			} else $thumbnail = array();

			// get global output parameters
			$output_arr = array();
			$outputattributes = array('video_codec','video_quality','video_speed','video_width','video_height','video_upscale','advanced_video_maximum_frame_rate','advanced_video_frame_rate','advanced_video_keyframe_interval','advanced_video_bitrate','advanced_video_bitrate_cap','advanced_video_buffer_size','advanced_video_skip_video','advanced_audio_bitrate','advanced_audio_sample_rate','advanced_audio_skip_video','video_autolevels','video_deblock ','start_clip','clip_length','public','video_denoise','advanced_audio_channels','advanced_video_deinterlace','audio_quality','audio_codec','aspect_mode');
			foreach ($outputattributes as $attrname) {
				$tmpvalue = get_option($this->prefix.'_'.$attrname);
				if ($tmpvalue && $tmpvalue != '') $output_arr[$attrname] = $tmpvalue;
			}

			// bulid PHP arrays to convert to JSON for zencoder call
			$notifications = array();
			$notifications['format'] = 'json';
			$notifications['url'] = $notifications_url;

			$zencoderjob_details = array();
			$zencoderjob_details['api_key'] = $api_key;
			$zencoderjob_details['input'] = $rawsrc;
			if ($test_mode) $zencoderjob_details['test'] = $test_mode;

			// build mp4 output
			$mp4output = array();
			$mp4output['notifications'] = array($notifications);
			$mp4output['label'] = $label_mp4;
			$mp4output['url'] = $outputurl.'.'.$label_mp4;
			if (!empty($thumbnail)) $mp4output['thumbnails'] = $thumbnail;
			if ($public) $mp4output['public'] = $public;

			// build ogg output
			$oggoutput = array();
			$oggoutput['notifications'] = array($notifications);
			$oggoutput['label'] = $label_ogg;
			$oggoutput['url'] = $outputurl.'.'.$label_ogg;
			if ($public) $oggoutput['public'] = $public;

			// build webm output
			$webmoutput = array();
			$webmoutput['notifications'] = array($notifications);
			$webmoutput['label'] = $label_webm;
			$webmoutput['url'] = $outputurl.'.'.$label_webm;
			if ($public) $webmoutput['public'] = $public;

			$zencoderjob_details['outputs'] = array(
				array_merge($mp4output,$output_arr),
				array_merge($oggoutput,$output_arr),
				array_merge($webmoutput,$output_arr)
				);

			return new ZencoderJob(json_encode($zencoderjob_details));
		}
		
		//function to show the form for creating zendcoder accout

		public function zencoder_show_form()
		{
			$acc_from='';
			
            $acc_from="<script>
				function zencoder_verify()
				{
					username=$('#zen_email').val();
					pass=$('#zen_password').val();
					cpass=$('#zen_confirm_password').val();
					terms=$('#zen_agreeterms').attr('checked');
					if(username.length<6 || pass.length<6){
						$('#err_msg').html('Password must be at lease 6 characters and email must be valid.');
						$('#err_msg').css('color','#ff0000');
						$('#err_msg').show();
					} else if (pass!=cpass) {
						$('#err_msg').html('Passwords do not match or do not meet minimum safety standards.');
						$('#err_msg').css('color','#ff0000');
						$('#err_msg').show();
					} else if (!terms) {
						$('#err_msg').html('You must agree to the terms and conditions');
						$('#err_msg').css('color','#ff0000');
						$('#err_msg').show();
					} else {
						$('#zen_password').val('');
						$('#zen_confirm_password').val('');
						$('#create_acc').val('creating account, please wait.');
						$.post('".get_option('siteurl')."/wp-admin/admin-ajax.php', {action:'html5_zen', 'cookie': encodeURIComponent(document.cookie),usern:username,passw:pass},
						function(str)
						{
							  if(str.indexOf('success')==-1)
							  {
								$('#create_acc').val('Create Account');
								alert(str);
							  }
							  else
							  {
								alert('Zencoder Account Created.');
							  }
								
						});
					}	
					return false;
				}
			</script>
			<script language='JavaScript'>
			<!-- Begin
			function popUp(URL) {
			day = new Date();
			id = day.getTime();
			eval(\"page\" + id + \" = window.open(URL, '\" + id + \"', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=990,height=400');\");
			}
			// End -->
			</script>
			<div class='wrap' style='width: 700px;'>
			<!--
			 /* These three items below must stay if you want to be able to easily save
				data in your settings pages. */
			-->
			<form method='post' action='' onsubmit='return zencoder_verify();'>
			". wp_nonce_field('update-options')."
			
			<!--
			// Now add each of those items below as a text box, checkbox, dropdown etc
			-->
			<fieldset>
				<legend>Create Zendcoder Account</legend>
				<p id='err_msg' style='display:none'></p>
				<label>Email:<br /></label> <input type='text' name='zen_email' value='' size='15' id='zen_email' /><br />
				<small>Email addres that you want to use to create zendcoder account.</small>
				<label>Password:<br /></label><input type='password' name='zen_password' value='' size='15' id='zen_password' /><br />
				<small>Password that you want use. It should be minimum 6 charaters in length</small>
				<label>Confirm Password:</label><input type='password' name='zen_confirm_password' value='' size='15' id='zen_confirm_password' /><br />
				<small></small>
				<label><a href=\"javascript:popUp('http://zencoder.com/terms/')\">Agree to Terms</a>:<br /></label> <input type='checkbox' name='zen_agreeterms' value='1' size='15' id='zen_agreeterms' /><br />
				<input type='submit' id='create_acc' value='Create Account'>
			</fieldset>
			
			<!-- ****************************************************** -->
			
		   </div>";
			return $acc_from;
		}
		
		
		function zencoder_create_account()
		{
			
			$request = new ZencoderRequest(
			  'https://app.zencoder.com/api/account', 
			  false, // API key isn't needed for new account creation
			  array(
				"terms_of_service" => "1",
				"email" => $_POST['usern'],
				"password" => $_POST['passw'],
				"affiliate_code"=> "mobilevid"
			  )
			);

			if ($request->successful) {
			  update_option("zencoder_api_key",$request->results['api_key']);
			  return 'success';
			} else {
			  $err='';
			  foreach($request->errors as $error) {
				$err .= $error."\n";
			  }
			  return $err;
			}

		}
		
		function getS3VideoBucketURL () {
			// get bucket location from options table
			$s3_bucket_loc = get_option($this->prefix.'_s3_bucket_location');
			// ensure a trailing slash on URL because we'll append a filename to it
			if ($s3_bucket_loc[strlen($s3_bucket_loc)-1] != '/') $s3_bucket_loc .= '/';

			return $s3_bucket_loc;
		}
		
		function getS3ThumbnailBucketURL () {
			// get bucket location from options table
			$s3_thumbnail_url = get_option($this->prefix.'_thumbnails_url');
			if ($s3_thumbnail_url == '') {
				// if no thumbnail URL then just use the Video bucket URL
				$s3_thumbnail_url = $this->getS3VideoBucketURL();
			} else {
				// ensure a trailing slash on URL because we'll append a filename to it
				if ($s3_thumbnail_url[strlen($s3_thumbnail_url)-1] != '/') $s3_thumbnail_url .= '/';
			}

			return $s3_thumbnail_url;
		}
		
		function createLogger()
		{
			$this->log = new html5_logger ($this->logging_filename , $this->logging_level);
			return true;
		}
		
		function getLogger() {
			return $this->log;
		}
	}
}
?>