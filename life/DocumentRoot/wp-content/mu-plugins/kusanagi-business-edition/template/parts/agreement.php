<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<form action="<?php echo admin_url( 'admin.php?page=kusanagi-be' ); ?>" method="post" class="postbox">
	<div class="inside">
		<p><?php _e( 'The question submission function of Business Edition.', 'kusanagi-be' ); ?></p>

		<?php if ( get_option( 'KUSANAGI_BE_agreement' ) ) : ?>
		<p class="meta-options">
			<label for="kusanagi-be-agreement"><input name="kusanagi-be-agreement" type="checkbox" id="kusanagi-be-agreement" value="open" checked="checked" disabled="disabled"> <?php _e( 'Ongoing service', 'kusanagi-be' ); ?></label>
		</p>
		<?php else : ?>
		<p class="meta-options">
			<label for="kusanagi-be-agreement"><input name="kusanagi-be-agreement" type="checkbox" id="kusanagi-be-agreement" value="open"> <?php _e( 'Start using', 'kusanagi-be' ); ?></label>
		</p>
		<input type="hidden" name="switch" value="kusanagi-be-agreement">
		<?php wp_nonce_field( 'kusanagi-be-agreement', 'kusanagi-be-agreement-nonce' ); ?>
		<p><input type="submit" class="button button-primary" value="<?php _e( 'Send', 'kusanagi-be' ); ?>"></p>
		<?php endif; ?>

	</div>
</form>
