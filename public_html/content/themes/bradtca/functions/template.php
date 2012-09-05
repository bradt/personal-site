<?php
function bt_photos_top() {
    ?>
	<div class="top">
		
		<h1>Photos</h1>
		
		<div class="nav">
			<a href="/photos/"<?php echo is_post_type_archive('photo_set') ? 'class="current"' : ''; ?>>Sets</a> <span class="sep">|</span>
			<a href="/photo-collections/"<?php echo (is_page('photo-collections') || is_tax('photo_collection')) ? 'class="current"' : ''; ?>>Collections</a> <span class="sep">|</span>
			<a href="/photo-tags/"<?php echo (is_page('photo-tags') || is_tax('photo_tag')) ? 'class="current"' : ''; ?>>Tags</a>
		</div>
	</div>
    <?php    
}