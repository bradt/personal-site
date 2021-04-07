<?php if ( ! is_front_page() ) : ?>

<?php get_search_form(); ?>

<footer class="site">
	<div class="nav-meta">
		<p class="copyright">
			Copyright &copy; 2004-<?php echo date( 'Y' ); ?> Brad Touesnard. All Rights Reserved.
		</p>
	</div>
</footer>

<?php endif; ?>

</div>

<?php wp_footer(); ?>

<?php if ( WP_LOCAL_DEV ) : ?>

	<script src="http://localhost:35730/livereload.js"></script>

<?php endif; ?>

</body>
</html>
