<?php

if( !defined( 'ABSPATH' ) ) die();

/**
 * Echoes the markup for a supplemental search engine settings UI
 *
 * @param null $engineName The engine name
 * @param null $engineLabel The engine label
 * @since 1.0
 */
function searchwpSupplementalEngineSettingsTemplate( $engineName = null, $engineLabel = null ) {
	global $searchwp;

	?>

	<li class="swp-supplemental-engine">
		<div class="swp-supplemental-engine-controls swp-group">
			<div class="swp-supplemental-engine-name">
				<a href="#" class="swp-supplemental-engine-edit-trigger"><?php
					if( is_null( $engineLabel ) )
					{
						echo '{{swp.engineLabel}}';
					}
					else
					{
						echo $engineLabel . ' <code>' . $engineName . '</code>';
					}
					?></a>
				<input type="text" name="<?php echo SEARCHWP_PREFIX; ?>settings[engines][<?php if( is_null( $engineName ) ) { ?>{{swp.engine}}<?php } { echo $engineName; } ?>][label]" value="<?php
				if( is_null( $engineLabel ) )
				{
					echo '{{swp.engineLabel}}';
				}
				else
				{
					echo $engineLabel;
				}
				?>" />
			</div>
			<div class="swp-supplemental-engine-delete">
				<a href="#" class="button swp-del-supplemental-engine"><?php _e( 'Remove', 'searchwp' ); ?></a>
			</div>
		</div>
		<div class="swp-supplemental-engine-settings"><?php
			if( is_null( $engineName ) )
			{
				echo '{{swp.engineSettings}}';
			}
			else
			{
				searchwpEngineSettingsTemplate( $engineName );
			}
		?></div>
	</li>

<?php
}
