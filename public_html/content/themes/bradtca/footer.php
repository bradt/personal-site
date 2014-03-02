</div>
</div>

<footer class="site">
	<div class="nav-meta">
		<h1><a href="/">Home</a></h1>
		<nav class="site">
			<?php bt_site_nav(); ?>
		</nav>
		<p class="copyright">
			Copyright &copy; 2004-<?php echo date( 'Y' ); ?> Brad Touesnard. All Rights Reserved.
		</p>
	</div>
</footer>

<script type="text/javascript">
function bradt_load_js_vars() {
    Bradt.template_url = '<?php echo get_stylesheet_directory_uri(); ?>';
}
</script>

<?php wp_footer(); ?>

</body>
</html>
