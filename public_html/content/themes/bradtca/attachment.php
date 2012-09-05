<?php get_header(); ?>

<div id="content" class="page photos single-photo">

	<?php bt_photos_top(); ?>

	<?php
	the_post();
	
	$views = get_post_meta(get_the_ID(), 'views', true);
	update_post_meta(get_the_ID(), 'views', $views+1);
	
	echo wp_get_attachment_link(get_the_ID(), 'large');

	$meta = wp_get_attachment_metadata(get_the_ID());
	?>
	<div class="details"<?php echo 'style="max-width: ' . $meta['sizes']['large']['width'] . 'px;"'; ?>>
		<div class="desc">
			<h2 class="subtitle"><?php the_title(); ?></h2>
			<div class="copy">
				<?php the_content(); ?>
			</div>
			<div class="tags">
				<?php the_terms(0, 'photo_tag', '<strong>Tags: </strong>'); ?>
			</div>
		</div>
		<table class="meta">
			<tr>
				<th>Date Taken:</th>
				<td><?php echo gmdate( 'Y-m-d H:i:s', ( $meta['image_meta']['created_timestamp'] + ( get_option( 'gmt_offset' ) * 3600 ) ) ); ?></td>
			</tr>
			<?php
			$display = array(
				'camera' => 'Camera',
				'focal_length' => 'Focal Length',
				'aperture' => 'Aperture',
				'iso' => 'ISO',
				'shutter_speed' => 'Shutter Speed'
			);
			
			foreach ($display as $key => $lbl) :
				if (isset($meta['image_meta'][$key]) && $meta['image_meta'][$key]) :
					echo '<tr><th>', $lbl, ':</th>';
					echo '<td>', $meta['image_meta'][$key], '</td></tr>';
				endif;
			endforeach;
			?>
			<tr>
				<th>License:</th>
				<td><a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/" title="Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-sa/3.0/88x31.png" /></a></td>
			</tr>
		</tbody>
		</table>

		<pre>
		<?php
		//print_r(wp_get_attachment_metadata(get_the_ID()));
		//print_r(get_post_custom());
		?>
		</pre>
	
	</div>

	<?php comments_template(); ?>
	
</div>

<?php get_footer(); ?>
