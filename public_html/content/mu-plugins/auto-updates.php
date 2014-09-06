<?php
// By default, it won't auto update to major versions (e.g. 3.7.1 -> 3.8 )
add_filter( 'allow_major_auto_core_updates', '__return_true' );

// Allow auto updates even though we have a .git folder
add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );