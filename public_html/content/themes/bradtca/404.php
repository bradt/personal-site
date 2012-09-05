<?php ob_start(); ?>
<?php header("HTTP/1.1 404 Not Found"); ?>
<?php header("Status: 404 Not Found"); ?>

<?php get_header(); ?>

<div id="content">

		<div class="post" style="height: 14em;">

			<h1>404 File Not Found</h1>
								
			<p>
				Sorry, the requested file could not be found.
			</p>
			
			<h2>Try searching what you're looking for...</h2>
			
			<?php include (TEMPLATEPATH . '/searchform.php'); ?>
		</div>
</div>

<?php get_footer(); ?>
