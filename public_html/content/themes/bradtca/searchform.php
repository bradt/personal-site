<?php
echo '<form role="search" method="get" class="search-form" action="' . esc_url( home_url( '/' ) ) . '">
	<input type="text" class="search-field" placeholder="' . esc_attr_x( 'Search &hellip;', 'placeholder' ) . '" name="s" />
</form>';
