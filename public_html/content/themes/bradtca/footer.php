<?php if (!is_page('main-page')) : ?>
</div>
<?php endif; ?>

<div id="footer">
	<div class="left">
		<a href="<?php bloginfo('url'); ?>" class="title"><?php bloginfo('name'); ?></a>
		Copyright &copy; 2004-<?php echo date("Y"); ?> <a href="<?php bloginfo('url'); ?>" title="<?php bloginfo('name'); ?>"><?php bloginfo('name'); ?></a>. All Rights Reserved.<br />
		Design by <a href="<?php bloginfo('url'); ?>">Brad Touesnard</a> Copyright &copy; <?php echo date('Y')?>.
	</div>
	<div class="right">
		<?php include (TEMPLATEPATH . '/searchform.php'); ?>
		<div id="hosted_by">Hosted by <br /><a href="http://www.zenutech.ca/?ref=bradt.ca">Zenutech</a></div>
		<div id="powered_by">Powered by <br /><a href="http://www.wordpress.org/">Wordpress</a></div>
	</div>
</div>

<script type="text/javascript">
function bradt_load_js_vars() {
	Bradt.template_url = '<?php bloginfo('template_url'); ?>';
}
</script>

<?php wp_footer(); ?>
</body>
</html>
