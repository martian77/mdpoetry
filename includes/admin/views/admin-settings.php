<?php
/**
 * Admin view: settings page.
 *
 * @package MDPoetry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<form method="post" action="options.php">
		<?php settings_fields( $options_name ); ?>
		<?php do_settings_sections( $page_name ); ?>

		<input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	</form>
</div>
