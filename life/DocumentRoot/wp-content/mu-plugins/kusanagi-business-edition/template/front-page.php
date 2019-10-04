<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<div id="wpbody" role="main">
	<div id="wpbody-content" aria-label="" tabindex="0">
		<div class="wrap">
			<h2>Business Edition <?php _e( 'Settings', 'kusanagi-be' ); ?></h2>

			<ul class="tab-menu">
				<li class="<?php echo KUSANAGI_BE_is_req_detail() ? 'tab-item' : 'current'; ?>"><a href="<?php echo admin_url( 'admin.php?page=kusanagi-be' ); ?>"><?php _e( 'Home', 'kusanagi-be' ); ?></a></li>
			</ul>

			<?php if ( $message = get_option( 'KUSANAGI_BE_message' ) ) : ?>
			<div class="updated"><p><?php echo esc_html( $message ); ?></p></div>
			<?php delete_option( 'KUSANAGI_BE_message' ); ?>
			<?php endif; ?>

			<?php if ( $error = get_option( 'KUSANAGI_BE_error' ) ) : ?>
			<div class="error"><p><?php echo esc_html( $error ); ?></p></div>
			<?php delete_option( 'KUSANAGI_BE_error' ); ?>
			<?php endif; ?>

			<?php
				if ( get_option( 'KUSANAGI_BE_agreement' ) ) :
					if ( KUSANAGI_BE_is_req_detail() ) :
						require_once KUSANAGI_BE_PLUGIN_DIR . 'template/parts/req-detail.php';
					else :
						require_once KUSANAGI_BE_PLUGIN_DIR . 'template/parts/agreement.php';
						require_once KUSANAGI_BE_PLUGIN_DIR . 'template/parts/home.php';
					endif;
				else :
					require_once KUSANAGI_BE_PLUGIN_DIR . 'template/parts/agreement.php';
				endif;
			?>
		</div>
	</div>
</div>
