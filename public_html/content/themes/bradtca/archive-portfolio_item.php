<?php 
$r = new WP_Query(array(
	'post_type' => 'page',
	'pagename' => 'portfolio'
));

$r->the_post();

get_header(); 
?>

<div id="content" class="page page-portfolio-list">

	<div class="top">
		
		<h1><?php the_title(); ?></h1>
		
		<div class="intro">
			<?php the_content(); ?>
		</div>
	</div>

	<ul class="tabs">
		<li class="featured"><a href="#featured" class="current">Featured</a></li>
		<li class="all"><a href="#all">All</a></li>
		<li class="html"><a href="#html">Frontend Development</a></li>
		<li class="wordpress-theme"><a href="#wordpress-theme">Wordpress Development</a></li>
		<li class="php-mysql"><a href="#php-mysql">PHP Development</a></li>
		<li class="design"><a href="#design">Design</a></li>
	</ul>

	<div class="tab-content">
	
		<ul class="projects">
			<?php
			wp_reset_postdata();

			if (have_posts()) : while (have_posts()) :
				the_post();
	
				$attachs = my_get_attachments();
				list($src, $width, $height) = wp_get_attachment_image_src($attachs[0]->ID, 'medium');
				?>
				<li class="project<?php echo (get_post_meta($post->ID, 'featured', true)) ? ' featured' : ''; ?>">
					<a href="<?php the_permalink(); ?>" class="scr">
						<img src="<?php echo $src; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" alt="" />
					</a>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> <?php edit_post_link('Edit'); ?></h2>

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
				</li>
			<?php endwhile; endif; ?>
		</ul>
		
	</div>

	<p class="old-portfolio" style="display: none;"><a href="/portfolio/archive/">Check out some of my older work &raquo;</a></p>

</div>

<?php get_footer(); ?>
