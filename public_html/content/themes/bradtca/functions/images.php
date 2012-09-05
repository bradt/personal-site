<?php
// Used lots of code from the reverted patch at http://core.trac.wordpress.org/changeset/11746/trunk/wp-includes/media.php
require_once ABSPATH . '/wp-admin/includes/image-edit.php';

bt_add_image_size('homethumb', 330, 190, true, array('src_x' => 0, 'src_y' => 0));
bt_add_image_size('medium', get_option('medium_size_w'), get_option('medium_size_h'), get_option('medium_crop'), array('src_y' => 0));
bt_add_image_size('photo_thumb', 330, 190, true, array('src_x' => 0, 'src_y' => 0));
bt_add_image_size('photo_home_hd', 500, 240, true);

function bt_add_image_size( $name, $width = 0, $height = 0, $crop = false, $extra = array() ) {
	global $_wp_additional_image_sizes;
	$_wp_additional_image_sizes[$name] = array( 'width' => absint( $width ), 'height' => absint( $height ), 'crop' => (bool) $crop, 'extra' => $extra );
}

function bt_intermediate_image_sizes_advanced( $sizes ) {
	// Returning no sizes (an empty array) will
	// force wp_generate_attachment_metadata to skip creating
	// intermediate image sizes on upload
	return array();
}
add_filter( 'intermediate_image_sizes_advanced', 'bt_intermediate_image_sizes_advanced' );

function bt_image_downsize( $deprecated, $id, $size ) {
	global $_wp_additional_image_sizes;
	
	// For registered, named sizes only, not array(width, height)
	if ( is_array($size) )
		return false;

	if ( !is_array( $imagedata = wp_get_attachment_metadata( $id ) ) )
		return false;

	$sizes = array();
	foreach ( get_intermediate_image_sizes() as $s ) {
		$sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => FALSE );
		if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
			$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
		else
			$sizes[$s]['width'] = get_option( "{$s}_size_w" ); // For default sizes set in options
		if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
			$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
		else
			$sizes[$s]['height'] = get_option( "{$s}_size_h" ); // For default sizes set in options
		if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
			$sizes[$s]['crop'] = intval( $_wp_additional_image_sizes[$s]['crop'] ); // For theme-added sizes
		else
			$sizes[$s]['crop'] = get_option( "{$s}_crop" ); // For default sizes set in options
		if ( isset( $_wp_additional_image_sizes[$s]['extra'] ) )
			$sizes[$s]['extra'] = $_wp_additional_image_sizes[$s]['extra']; // For theme-added sizes
		else
			$sizes[$s]['extra'] = get_option( "{$s}_extra" ); // For default sizes set in options
			
		if ( !is_array( $sizes[$s]['extra'] ) ) $sizes[$s]['extra'] = array();
	}
	
	// If it's not a registered intermediate size
	if ( !isset( $sizes[$size] ) )
		return false;

	$size_info = $sizes[$size];

	// If the image has not been resized already
	if ( !isset( $imagedata['sizes'][$size] ) ) {
		$uploadpath = wp_upload_dir();
		$file = path_join( $uploadpath['basedir'], $imagedata['file'] );
		
		$resized = bt_image_make_intermediate_size( $file, $size_info['width'], $size_info['height'], $size_info['crop'], $size_info['extra'] );
		if ( $resized ) {
			$imagedata['sizes'][$size] = $resized;
			wp_update_attachment_metadata( $id, $imagedata );
		}
	}
	
	return false;
}
add_filter( 'image_downsize', 'bt_image_downsize', 10, 3 );

/**
 * Resize an image to make a thumbnail or intermediate size.
 *
 * The returned array has the file size, the image width, and image height. The
 * filter 'image_make_intermediate_size' can be used to hook in and change the
 * values of the returned array. The only parameter is the resized file path.
 *
 * @since 2.5.0
 *
 * @param string $file File path.
 * @param int $width Image width.
 * @param int $height Image height.
 * @param bool $crop Optional, default is false. Whether to crop image to specified height and width or resize.
 * @return bool|array False, if no image was created. Metadata array on success.
 */
function bt_image_make_intermediate_size( $file, $width, $height, $crop=false, $extra=array() ) {
	if ( $width || $height ) {
		$resized_file = bt_image_resize($file, $width, $height, $crop, null, null, 90, $extra);
		if ( !is_wp_error($resized_file) && $resized_file && $info = getimagesize($resized_file) ) {
			$resized_file = apply_filters('image_make_intermediate_size', $resized_file);
			return array(
				'file' => wp_basename( $resized_file ),
				'width' => $info[0],
				'height' => $info[1],
			);
		}
	}
	return false;
}

/**
 * Scale down an image to fit a particular size and save a new copy of the image.
 *
 * The PNG transparency will be preserved using the function, as well as the
 * image type. If the file going in is PNG, then the resized image is going to
 * be PNG. The only supported image types are PNG, GIF, and JPEG.
 *
 * Some functionality requires API to exist, so some PHP version may lose out
 * support. This is not the fault of WordPress (where functionality is
 * downgraded, not actual defects), but of your PHP version.
 *
 * @since 2.5.0
 *
 * @param string $file Image file path.
 * @param int $max_w Maximum width to resize to.
 * @param int $max_h Maximum height to resize to.
 * @param bool $crop Optional. Whether to crop image or resize.
 * @param string $suffix Optional. File Suffix.
 * @param string $dest_path Optional. New image file path.
 * @param int $jpeg_quality Optional, default is 90. Image quality percentage.
 * @param array $extra Optional, extra parameters that will be extracted just before creating the resized image
 * @return mixed WP_Error on failure. String with new destination path.
 */
function bt_image_resize( $file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90, $extra = array() ) {

	$image = wp_load_image( $file );
	if ( !is_resource( $image ) )
		return new WP_Error( 'error_loading_image', $image, $file );

	$size = @getimagesize( $file );
	if ( !$size )
		return new WP_Error('invalid_image', __('Could not read image size'), $file);
	list($orig_w, $orig_h, $orig_type) = $size;

	$rotate = false;
	if ( is_callable( 'exif_read_data' ) && in_array( $orig_type, apply_filters( 'wp_read_image_metadata_types', array( IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM ) ) ) ) {
		// rotate if EXIF 'Orientation' is set
		$exif = @exif_read_data( $file, null, true );
		if ( $exif && isset( $exif['IFD0'] ) && is_array( $exif['IFD0'] ) && isset( $exif['IFD0']['Orientation'] ) ) {
			if ( 6 == $exif['IFD0']['Orientation'] )
				$rotate = 90;
			elseif ( 8 == $exif['IFD0']['Orientation'] )
				$rotate = 270;
		}
	}
	
	if ( $rotate )
		list($max_h,$max_w) = array($max_w,$max_h);

	$dims = image_resize_dimensions($orig_w, $orig_h, $max_w, $max_h, $crop);
	if ( !$dims )
		return new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );
	list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;

	$newimage = wp_imagecreatetruecolor( $dst_w, $dst_h );
	
	extract( $extra );

	if ( $rotate )
		list($src_y,$src_x) = array($src_x,$src_y);

	imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

	// convert from full colors to index colors, like original PNG.
	if ( IMAGETYPE_PNG == $orig_type && function_exists('imageistruecolor') && !imageistruecolor( $image ) )
		imagetruecolortopalette( $newimage, false, imagecolorstotal( $image ) );

	// we don't need the original in memory anymore
	imagedestroy( $image );

	// $suffix will be appended to the destination filename, just before the extension
	if ( !$suffix ) {
		if ( $rotate )
			$suffix = "{$dst_h}x{$dst_w}";
		else
			$suffix = "{$dst_w}x{$dst_h}";
	}

	$info = pathinfo($file);
	$dir = $info['dirname'];
	$ext = $info['extension'];
	$name = wp_basename($file, ".$ext");

	if ( !is_null($dest_path) and $_dest_path = realpath($dest_path) )
		$dir = $_dest_path;
	$destfilename = "{$dir}/{$name}-{$suffix}.{$ext}";

	if ( IMAGETYPE_GIF == $orig_type ) {
		if ( !imagegif( $newimage, $destfilename ) )
			return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
	} elseif ( IMAGETYPE_PNG == $orig_type ) {
		if ( !imagepng( $newimage, $destfilename ) )
			return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
	} else {
		if ( $rotate ) {
			$newimage = _rotate_image_resource( $newimage, 360 - $rotate );
		}
		
		// all other formats are converted to jpg
		$destfilename = "{$dir}/{$name}-{$suffix}.jpg";
		$return = imagejpeg( $newimage, $destfilename, apply_filters( 'jpeg_quality', $jpeg_quality, 'image_resize' ) );
		if ( !$return )
			return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
	}

	imagedestroy( $newimage );

	// Set correct file permissions
	$stat = stat( dirname( $destfilename ));
	$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
	@ chmod( $destfilename, $perms );

	return $destfilename;
}
