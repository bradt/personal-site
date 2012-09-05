<?php get_header(); the_post(); ?>

<div id="content" class="page page-portfolio">

	<div class="top">
		
		<h1>Portfolio</h1>
		
		<div class="intro">
			<a href="/portfolio/">&laquo; Back to portfolio listing</a>
		</div>
	</div>
	
	<?php
	$attachments = my_get_attachments();
	$attach_count = count($attachments);

	foreach ($attachments as $attachment) {
		list($src, $width, $height) = wp_get_attachment_image_src($attachment->ID, 'fullsize');
		break;
	}
	$max_width = 'max-width: ' . $width . 'px;';
	?>

	<a name="<?php echo $post->post_name; ?>"></a>
	
	<div class="project" style="<?php echo $max_width; ?>">
	
		<div class="details">
			<div class="desc">
				<h2><?php the_title(); ?> <span class="date">// <?php the_time('F Y') ?></span></h2>
				<div class="copy">
					<?php the_content(); ?>
				</div>
			</div>
			<div class="roles">
				<h4>My Roles</h4>
				<ul>
				<?php
				$tags = get_the_terms($post->ID, 'post_tag');
				if ($tags) {
					$i = 1;
					foreach ($tags as $tag) {
						$css = ($i % 2 == 0) ? ' class="even"' : '';
						printf('<li%s><span>%s</span></li>', $css, $tag->name);
						$i++;
					}
				}
				?>
				</ul>
			</div>
		</div>
	
		<div class="scr">
			<a href="<?php echo $src; ?>" style="background-image: url(<?php echo $src; ?>); height: <?php echo $height; ?>px;"><?php the_title(); ?></a>
		</div>
	
		<?php
		$credit = get_post_meta($post->ID, 'credit', true);
		if ($credit) :
		?>
		<p class="credit">
			Design by <?php echo $credit; ?>
		</p>
		<?php endif; ?>
	
		<?php if (count($attachments) > 1) : ?>
		
		<ul class="screenshots">
		
		<?php
		$i = 0;
		foreach ($attachments as $attachment) {
			list($src, $width, $height) = wp_get_attachment_image_src($attachment->ID);
			list($link, $x, $y) = wp_get_attachment_image_src($attachment->ID, 'fullsize');
			
			if ($i == 0) {
				$current = ' class="current"';
			}
			else {
				$current = '';
			}
			
			printf('<li><a href="%s"%s style="background-image: url(%s);" title="%s">%s</a></li>', $link, $current, $src, attribute_escape($attachment->post_title), $attachment->post_title);
			
			$i++;
		}
		?>
	
		</ul>
		
		<?php endif; ?>
		
	</div>

	<p>
		<a href="/portfolio/">&laquo; Back to portfolio listing</a>
	</p>

</div>

<?php get_footer(); ?>
