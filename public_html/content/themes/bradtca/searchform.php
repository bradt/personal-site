<?php
echo '<form role="search" method="get" class="search-form" action="' . esc_url( home_url( '/' ) ) . '">
	<input type="search" class="search-field" placeholder="' . esc_attr_x( 'Search &hellip;', 'placeholder' ) . '" value="' . get_search_query() . '" name="s" />
	<input type="submit" class="search-submit" value="' . esc_attr_x( 'Search', 'submit button' ) . '" />
</form>';
