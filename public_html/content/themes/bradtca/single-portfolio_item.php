<?php get_header(); the_post(); ?>

<section class="page page-portfolio">

	<div class="top">
		
		<h1 class="page-title">Portfolio</h1>
		
		<div class="intro">
			<a href="/portfolio/">&laquo; Back to portfolio listing</a>
		</div>
	</div>
	
	<a name="<?php echo $post->post_name; ?>"></a>
	
	<div class="project">
	
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
				$tags = get_the_terms($post->ID, 'portfolio_tag');
				if ($tags) {
					$i = 1;
					foreach ($tags as $tag) {
						$css = ($i % 2 == 0) ? ' class="even"' : '';
						printf('<li%s>%s</li>', $css, $tag->name);
						$i++;
					}
				}
				?>
				</ul>
			</div>
		</div>
	
		<?php 
		$attachments = bt_get_attachments();

		if (count($attachments) > 1) : ?>
		
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
			
			printf('<li%s><a href="%s" style="background-image: url(%s);" title="%s">%s</a></li>', $current, $link, $src, attribute_escape($attachment->post_title), $attachment->post_title);
			
			$i++;
		}
		?>
	
		</ul>
		
		<?php endif; ?>
	
		<?php
		$credit = get_post_meta($post->ID, 'credit', true);
		if ($credit) :
		?>
		<p class="credit">
			Design by <?php echo $credit; ?>
		</p>
		<?php endif; ?>

		<?php
		list($link, $x, $y) = wp_get_attachment_image_src($attachments[0]->ID, 'fullsize');
		?>
	
		<a class="scr" href="<?php echo $link; ?>">
			<?php echo wp_get_attachment_image($attachments[0]->ID, 'fullsize'); ?>
		</a>
		
	</div>

	<p>
		<a href="/portfolio/">&laquo; Back to portfolio listing</a>
	</p>

</section>

<?php get_footer(); ?>
